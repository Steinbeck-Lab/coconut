<?php

namespace App\Services\OrganismTaxonomy;

use App\Models\Organism;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrganismTaxonomyMapper
{
    private const NCBITAXON_RANK_PROPERTY = 'http://purl.obolibrary.org/obo/ncbitaxon#has_rank';

    private const RANK_IRIS = [
        'species' => 'http://purl.obolibrary.org/obo/NCBITaxon_species',
        'genus' => 'http://purl.obolibrary.org/obo/NCBITaxon_genus',
        'family' => 'http://purl.obolibrary.org/obo/NCBITaxon_family',
    ];

    private ClientInterface $olsClient;

    private ClientInterface $gnfClient;

    /** @var array<string, array<int, array<string, mixed>>|null> */
    private array $olsElementsCache = [];

    public function __construct(
        private readonly OrganismNameNormalizer $nameNormalizer = new OrganismNameNormalizer,
        private readonly GnfTaxonomyParser $taxonomyParser = new GnfTaxonomyParser,
        private readonly ?GnfMatchGate $matchGate = null,
        private readonly ?OrganismCurationResolver $curationResolver = null,
        ?ClientInterface $olsClient = null,
        ?ClientInterface $gnfClient = null,
    ) {
        $timeout = (int) config('services.organism_taxonomy.http_timeout', 30);

        $this->olsClient = $olsClient ?? new Client([
            'base_uri' => config('services.organism_taxonomy.ols_base_uri'),
            'timeout' => $timeout,
        ]);

        $this->gnfClient = $gnfClient ?? new Client([
            'timeout' => $timeout,
        ]);
    }

    public function map(string $rawName): OrganismTaxonomyMappingResult
    {
        $this->olsElementsCache = [];

        $lookupName = $this->nameNormalizer->normalizeForLookup($rawName);

        if ($lookupName === '') {
            return OrganismTaxonomyMappingResult::unmapped($lookupName);
        }

        $taxonomy = $this->fetchVerifierTaxonomy($lookupName);

        if ($taxonomy !== null && ! $this->matchGate()->acceptsTaxonomyProfile($taxonomy, $lookupName)) {
            $taxonomy = null;
        }

        $iri = null;
        $rank = null;
        $source = 'none';
        $matchType = null;
        $canonicalName = $taxonomy['canonical_name'] ?? null;

        $olsCandidates = [
            'species' => $lookupName,
            'genus' => $this->nameNormalizer->firstToken($lookupName),
            'family' => $this->nameNormalizer->firstToken($lookupName),
        ];

        foreach ($olsCandidates as $candidateRank => $searchName) {
            if ($searchName === null || $searchName === '') {
                continue;
            }

            $olsIri = $this->matchOlsIri($searchName, $candidateRank);

            if ($olsIri !== null) {
                $iri = $olsIri;
                $rank = $candidateRank;
                $source = 'ols';
                break;
            }
        }

        if ($iri === null) {
            $finderResult = $this->lookupFinderMapping($lookupName);

            if ($finderResult !== null) {
                $iri = $finderResult->iri;
                $rank = $finderResult->rank;
                $source = $finderResult->source;
                $matchType = $finderResult->matchType;
                $taxonomy = $taxonomy ?? $finderResult->taxonomy;
                $canonicalName = $canonicalName ?? $finderResult->canonicalName;
            }
        }

        if ($iri === null && $taxonomy !== null) {
            $fallback = $this->mappingFromTaxonomy($taxonomy, $lookupName);

            if ($fallback !== null) {
                $iri = $fallback->iri;
                $rank = $fallback->rank;
                $source = $fallback->source;
                $matchType = $fallback->matchType;
            }
        }

        if ($iri === null && $taxonomy === null) {
            return OrganismTaxonomyMappingResult::unmapped($lookupName);
        }

        if ($matchType === null && $taxonomy !== null) {
            $matchType = $taxonomy['match_type'] ?? null;
        }

        if ($rank === null && $taxonomy !== null) {
            $rank = $this->extractTrailingRankFromLineage($taxonomy['lineage'] ?? []);
        }

        return new OrganismTaxonomyMappingResult(
            iri: $iri,
            rank: $rank,
            source: $source,
            matchType: $matchType,
            lookupName: $lookupName,
            taxonomy: $taxonomy,
            canonicalName: $canonicalName,
        );
    }

    public function mapOrganism(Organism $organism, bool $applyCuration = true): OrganismTaxonomyMappingResult
    {
        $result = $this->map($organism->name);

        if (! $applyCuration || $result->taxonomy !== null) {
            return $result;
        }

        $resolver = $this->curationResolver();
        $fallbackCandidates = $resolver->fallbackLookupCandidates($organism);

        if ($fallbackCandidates === []) {
            return $result;
        }

        $resolved = $this->resolveFirstVerifierTaxonomyFromCandidates($fallbackCandidates);

        if ($resolved === null) {
            return $result;
        }

        $curatedResult = $this->map($resolved['resolved_lookup']);

        if ($curatedResult->taxonomy === null) {
            return $result;
        }

        $classification = $resolver->classify($organism);
        $taxonomy = $resolver->annotateResolvedTaxonomy(
            $curatedResult->taxonomy,
            $organism,
            $resolved['resolved_lookup'],
            $classification,
        );

        return new OrganismTaxonomyMappingResult(
            iri: $curatedResult->iri ?? $result->iri,
            rank: $curatedResult->rank ?? $result->rank,
            source: $curatedResult->source !== 'none' ? $curatedResult->source : $result->source,
            matchType: $taxonomy['match_type'] ?? $curatedResult->matchType,
            lookupName: $resolved['resolved_lookup'],
            taxonomy: $taxonomy,
            canonicalName: $taxonomy['canonical_name'] ?? $curatedResult->canonicalName,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchVerifierTaxonomy(string $lookupName): ?array
    {
        return $this->fetchVerifierTaxonomyBatch([$lookupName])[$lookupName] ?? null;
    }

    /**
     * Try GNF verifier lookups in order and return the first acceptable exact-match profile.
     *
     * @param  list<string>  $lookupNames
     * @return array{taxonomy: array<string, mixed>, resolved_lookup: string}|null
     */
    public function resolveFirstVerifierTaxonomyFromCandidates(array $lookupNames): ?array
    {
        $orderedNames = [];

        foreach ($lookupNames as $lookupName) {
            $normalized = $this->nameNormalizer->normalizeForLookup($lookupName);

            if ($normalized === '') {
                continue;
            }

            $duplicate = false;

            foreach ($orderedNames as $existing) {
                if ($this->nameNormalizer->equalsForLookup($existing, $normalized)) {
                    $duplicate = true;

                    break;
                }
            }

            if (! $duplicate) {
                $orderedNames[] = $normalized;
            }
        }

        if ($orderedNames === []) {
            return null;
        }

        $profiles = $this->fetchVerifierTaxonomyBatch($orderedNames);

        foreach ($orderedNames as $lookupName) {
            $profile = $profiles[$lookupName] ?? null;

            if ($profile !== null && $this->matchGate()->acceptsTaxonomyProfile($profile, $lookupName)) {
                return [
                    'taxonomy' => $profile,
                    'resolved_lookup' => $lookupName,
                ];
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lookupNames
     * @return array<string, array<string, mixed>|null>
     */
    public function fetchVerifierTaxonomyBatch(array $lookupNames): array
    {
        $lookupNames = array_values(array_unique(array_filter(
            $lookupNames,
            static fn (string $name): bool => trim($name) !== '',
        )));

        if ($lookupNames === []) {
            return [];
        }

        $nameStrings = $this->normalizedNameStrings($lookupNames);

        if ($nameStrings === []) {
            return array_fill_keys($lookupNames, null);
        }

        try {
            $response = $this->gnfClient->post(
                config('services.organism_taxonomy.gnf_verifier_url'),
                [
                    'json' => [
                        'nameStrings' => $nameStrings,
                    ],
                ],
            );

            $responseBody = json_decode((string) $response->getBody(), true);

            if (! is_array($responseBody)) {
                return array_fill_keys($lookupNames, null);
            }

            return $this->finalizeVerifierBatchProfiles(
                $this->taxonomyParser->parseVerifierBatchResponse($responseBody, $lookupNames),
            );
        } catch (Throwable $e) {
            Log::error('GNF verifier taxonomy batch lookup failed', [
                'names_count' => count($lookupNames),
                'error' => $e->getMessage(),
            ]);

            return array_fill_keys($lookupNames, null);
        } finally {
            $this->throttle();
        }
    }

    /**
     * @param  list<list<string>>  $batches
     * @return array<string, array<string, mixed>|null>
     */
    public function fetchVerifierTaxonomyParallel(array $batches, int $concurrency): array
    {
        $batches = array_values(array_filter(
            $batches,
            static fn (array $batch): bool => $batch !== [],
        ));

        if ($batches === []) {
            return [];
        }

        if ($concurrency <= 1 || count($batches) === 1) {
            $results = [];

            foreach ($batches as $batch) {
                $results = array_merge($results, $this->fetchVerifierTaxonomyBatch($batch));
            }

            return $results;
        }

        $url = (string) config('services.organism_taxonomy.gnf_verifier_url');
        $merged = [];

        $requests = function () use ($batches, $url) {
            foreach ($batches as $index => $batch) {
                $nameStrings = $this->normalizedNameStrings($batch);

                if ($nameStrings === []) {
                    continue;
                }

                yield $index => function () use ($url, $nameStrings) {
                    return $this->gnfClient->requestAsync('POST', $url, [
                        'json' => [
                            'nameStrings' => $nameStrings,
                        ],
                    ]);
                };
            }
        };

        try {
            $pool = new Pool($this->gnfClient, $requests(), [
                'concurrency' => $concurrency,
                'fulfilled' => function ($response, int|string $index) use ($batches, &$merged): void {
                    $batch = $batches[$index] ?? [];

                    if ($batch === []) {
                        return;
                    }

                    $responseBody = json_decode((string) $response->getBody(), true);

                    if (! is_array($responseBody)) {
                        foreach ($batch as $name) {
                            $merged[$name] = null;
                        }

                        return;
                    }

                    $merged = array_merge(
                        $merged,
                        $this->finalizeVerifierBatchProfiles(
                            $this->taxonomyParser->parseVerifierBatchResponse($responseBody, $batch),
                        ),
                    );
                },
                'rejected' => function ($reason, int|string $index) use ($batches): void {
                    $batch = $batches[$index] ?? [];

                    Log::error('GNF verifier parallel batch failed', [
                        'names_count' => count($batch),
                        'error' => $reason instanceof GuzzleException ? $reason->getMessage() : (string) $reason,
                    ]);
                },
            ]);

            $pool->promise()->wait();
        } catch (Throwable $e) {
            Log::error('GNF verifier parallel pool failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->throttle();
        }

        foreach ($batches as $batch) {
            foreach ($batch as $name) {
                $merged[$name] ??= null;
            }
        }

        return $merged;
    }

    public function parseGnfResponse(array $responseBody, string $lookupName): ?OrganismTaxonomyMappingResult
    {
        $taxonomy = $this->taxonomyParser->parseFinderResponse($responseBody, $lookupName);

        if ($taxonomy === null) {
            return null;
        }

        $matchType = $taxonomy['match_type'] ?? null;

        if (! $this->matchGate()->acceptsMatchType($matchType)) {
            return null;
        }

        $fallback = $this->mappingFromTaxonomy($taxonomy, $lookupName);

        if ($fallback === null) {
            return null;
        }

        return new OrganismTaxonomyMappingResult(
            iri: $fallback->iri,
            rank: $fallback->rank,
            source: 'gnf',
            matchType: $matchType,
            lookupName: $lookupName,
            taxonomy: $taxonomy,
            canonicalName: $taxonomy['canonical_name'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     */
    private function mappingFromTaxonomy(array $taxonomy, string $lookupName): ?OrganismTaxonomyMappingResult
    {
        $matchType = $taxonomy['match_type'] ?? null;

        if (! $this->matchGate()->acceptsMatchType($matchType)) {
            return null;
        }

        if (! $this->matchGate()->acceptsTaxonomyProfile($taxonomy, $lookupName)) {
            return null;
        }

        $iri = $taxonomy['references'][0]['url'] ?? null;

        if (! is_string($iri) || $iri === '') {
            $source = $taxonomy['data_source'] ?? null;
            $lineage = $taxonomy['lineage'] ?? [];

            if (is_string($source) && $source !== '' && count($lineage) >= 2) {
                $parent = $lineage[count($lineage) - 2];
                $iri = $source.'['.($parent['name'] ?? '').'|'.($parent['id'] ?? '').']';
            }
        }

        if (! is_string($iri) || $iri === '') {
            return null;
        }

        $lineage = $taxonomy['lineage'] ?? [];
        $rank = $this->extractTrailingRankFromLineage($lineage);

        $taxonomy['fetched_at'] = $taxonomy['fetched_at'] ?? now()->utc()->toIso8601String();

        return new OrganismTaxonomyMappingResult(
            iri: $this->encodeIriForStorage($iri),
            rank: $rank,
            source: 'gnf',
            matchType: is_string($matchType) ? $matchType : null,
            lookupName: $lookupName,
            taxonomy: $taxonomy,
            canonicalName: $taxonomy['canonical_name'] ?? null,
        );
    }

    private function lookupFinderMapping(string $lookupName): ?OrganismTaxonomyMappingResult
    {
        try {
            $response = $this->gnfClient->post(
                config('services.organism_taxonomy.gnf_finder_url'),
                [
                    'json' => [
                        'text' => $lookupName,
                        'bytesOffset' => false,
                        'returnContent' => false,
                        'uniqueNames' => true,
                        'ambiguousNames' => false,
                        'noBayes' => false,
                        'oddsDetails' => false,
                        'language' => 'eng',
                        'wordsAround' => 0,
                        'verification' => true,
                        'allMatches' => true,
                    ],
                ],
            );

            $responseBody = json_decode((string) $response->getBody(), true);

            if (! is_array($responseBody)) {
                return null;
            }

            return $this->parseGnfResponse($responseBody, $lookupName);
        } catch (Throwable $e) {
            Log::error('GNF finder taxonomy lookup failed', [
                'lookup_name' => $lookupName,
                'error' => $e->getMessage(),
            ]);

            return null;
        } finally {
            $this->throttle();
        }
    }

    private function matchOlsIri(string $name, string $rank): ?string
    {
        if (! isset(self::RANK_IRIS[$rank])) {
            return null;
        }

        $elements = $this->fetchOlsElements($name);

        if ($elements === null || $elements === []) {
            return null;
        }

        $element = $elements[0];

        if (! is_array($element) || ! $this->isActiveTerm($element)) {
            return null;
        }

        if (! $this->olsLabelMatches($name, $element)) {
            return null;
        }

        $elementRank = $element[self::NCBITAXON_RANK_PROPERTY] ?? null;

        if ($elementRank !== self::RANK_IRIS[$rank]) {
            return null;
        }

        $iri = $element['iri'] ?? null;

        if (! is_string($iri) || $iri === '') {
            return null;
        }

        return $this->encodeIriForStorage($iri);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchOlsElements(string $name): ?array
    {
        if (array_key_exists($name, $this->olsElementsCache)) {
            return $this->olsElementsCache[$name];
        }

        try {
            $response = $this->olsClient->get('entities', [
                'query' => [
                    'search' => $name,
                    'ontologyId' => 'ncbitaxon',
                    'exactMatch' => true,
                    'type' => 'class',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $elements = (is_array($data) && isset($data['elements']) && is_array($data['elements']))
                ? $data['elements']
                : [];

            $this->olsElementsCache[$name] = $elements;

            return $elements;
        } catch (Throwable $e) {
            Log::error('OLS taxonomy lookup failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            $this->olsElementsCache[$name] = null;

            return null;
        } finally {
            $this->throttle();
        }
    }

    /**
     * @param  list<array{rank: string, name: string, id: string|null}>  $lineage
     */
    private function extractTrailingRankFromLineage(array $lineage): ?string
    {
        if ($lineage === []) {
            return null;
        }

        $last = $lineage[array_key_last($lineage)];

        return $last['rank'] ?? null;
    }

    private function isActiveTerm(array $element): bool
    {
        if (! isset($element['iri'], $element['ontologyId'])) {
            return false;
        }

        $obsolete = $element['isObsolete'] ?? false;

        return ! in_array($obsolete, [true, 'true', 1, '1'], true);
    }

    private function encodeIriForStorage(string $iri): string
    {
        if (str_starts_with($iri, 'http://') || str_starts_with($iri, 'https://')) {
            return urlencode($iri);
        }

        return $iri;
    }

    private function throttle(): void
    {
        $milliseconds = (int) config('services.organism_taxonomy.throttle_ms', 200);

        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }

    /**
     * @param  list<string>  $lookupNames
     * @return list<string>
     */
    private function normalizedNameStrings(array $lookupNames): array
    {
        $nameStrings = [];

        foreach ($lookupNames as $lookupName) {
            $normalized = $this->nameNormalizer->normalizeForLookup($lookupName);

            if ($normalized !== '') {
                $nameStrings[] = $normalized;
            }
        }

        return array_values(array_unique($nameStrings));
    }

    /**
     * @param  array<string, array<string, mixed>|null>  $profiles
     * @return array<string, array<string, mixed>|null>
     */
    private function finalizeVerifierBatchProfiles(array $profiles): array
    {
        $fetchedAt = now()->utc()->toIso8601String();

        foreach ($profiles as $lookupName => $profile) {
            if ($profile === null) {
                continue;
            }

            if (! $this->matchGate()->acceptsTaxonomyProfile($profile, $lookupName)) {
                $profiles[$lookupName] = null;

                continue;
            }

            $profile['fetched_at'] = $fetchedAt;
            $profiles[$lookupName] = $profile;
        }

        return $profiles;
    }

    private function matchGate(): GnfMatchGate
    {
        return $this->matchGate ?? GnfMatchGate::fromConfig();
    }

    private function curationResolver(): OrganismCurationResolver
    {
        return $this->curationResolver ?? new OrganismCurationResolver($this->nameNormalizer);
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function olsLabelMatches(string $queryName, array $element): bool
    {
        $labels = [];

        if (isset($element['label']) && is_string($element['label'])) {
            $labels[] = $element['label'];
        }

        if (isset($element['http://www.w3.org/2000/01/rdf-schema#label']) && is_string($element['http://www.w3.org/2000/01/rdf-schema#label'])) {
            $labels[] = $element['http://www.w3.org/2000/01/rdf-schema#label'];
        }

        if ($labels === []) {
            return true;
        }

        foreach ($labels as $label) {
            if ($this->nameNormalizer->equalsForLookup($queryName, $label)) {
                return true;
            }
        }

        return false;
    }
}
