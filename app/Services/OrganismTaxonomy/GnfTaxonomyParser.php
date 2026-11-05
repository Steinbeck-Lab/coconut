<?php

namespace App\Services\OrganismTaxonomy;

class GnfTaxonomyParser
{
    private const DISPLAY_RANKS = [
        'kingdom',
        'phylum',
        'class',
        'order',
        'family',
        'genus',
        'species',
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function parseVerifierResponse(array $responseBody, string $lookupName): ?array
    {
        $profiles = $this->parseVerifierBatchResponse($responseBody, [$lookupName]);

        return $profiles[$lookupName] ?? null;
    }

    /**
     * @param  list<string>  $lookupNames
     * @return array<string, array<string, mixed>|null>
     */
    public function parseVerifierBatchResponse(array $responseBody, array $lookupNames): array
    {
        $results = array_fill_keys($lookupNames, null);

        if (! isset($responseBody['names']) || ! is_array($responseBody['names'])) {
            return $results;
        }

        $normalizer = new OrganismNameNormalizer;
        $pending = [];

        foreach ($lookupNames as $lookupName) {
            $pending[$normalizer->normalizeForLookup($lookupName)] = $lookupName;
        }

        foreach ($responseBody['names'] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $bestResult = is_array($entry['bestResult'] ?? null) ? $entry['bestResult'] : [];
            $responseName = $entry['name']
                ?? $bestResult['matchedCanonicalSimple']
                ?? $bestResult['matchedName']
                ?? null;

            if (! is_string($responseName) || $responseName === '') {
                continue;
            }

            $normalizedResponseName = $normalizer->normalizeForLookup($responseName);

            if (! isset($pending[$normalizedResponseName])) {
                continue;
            }

            $lookupName = $pending[$normalizedResponseName];
            $results[$lookupName] = $this->parseMatchEntry($entry, $lookupName, 'verifier');
        }

        return $results;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function parseFinderResponse(array $responseBody, string $lookupName): ?array
    {
        if (! isset($responseBody['names']) || ! is_array($responseBody['names']) || $responseBody['names'] === []) {
            return null;
        }

        $entry = $responseBody['names'][0];
        $verification = $entry['verification'] ?? null;

        if (! is_array($verification)) {
            return null;
        }

        return $this->parseMatchEntry($verification, $lookupName, 'finder');
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>|null
     */
    private function parseMatchEntry(array $entry, string $lookupName, string $api): ?array
    {
        $matchType = $entry['matchType'] ?? null;
        $bestResult = $entry['bestResult'] ?? null;

        if (! is_string($matchType) || $matchType === 'NoMatch' || ! is_array($bestResult)) {
            return null;
        }

        $lineage = $this->buildLineage(
            $bestResult['classificationRanks'] ?? null,
            $bestResult['classificationPath'] ?? null,
            $bestResult['classificationIds'] ?? null,
        );

        $canonicalName = $bestResult['matchedCanonicalSimple']
            ?? $bestResult['currentCanonicalSimple']
            ?? $lookupName;

        $references = $this->buildReferences($bestResult);

        return [
            'api' => $api,
            'lookup_name' => $lookupName,
            'canonical_name' => $canonicalName,
            'matched_name' => $bestResult['matchedName'] ?? null,
            'match_type' => $matchType,
            'taxonomic_status' => $bestResult['taxonomicStatus'] ?? null,
            'is_synonym' => (bool) ($bestResult['isSynonym'] ?? false),
            'data_source' => $bestResult['dataSourceTitleShort'] ?? null,
            'data_source_id' => $bestResult['dataSourceId'] ?? null,
            'record_id' => $bestResult['recordId'] ?? null,
            'biological_group' => $this->resolveBiologicalGroup($lineage),
            'lineage' => $lineage,
            'ranks' => $this->filterDisplayRanks($lineage),
            'references' => $references,
            'edit_distance' => $bestResult['editDistance'] ?? null,
        ];
    }

    /**
     * @return list<array{rank: string, name: string, id: string|null}>
     */
    public function buildLineage(?string $ranks, ?string $paths, ?string $ids): array
    {
        $rankParts = $this->splitPipeDelimited($ranks);
        $pathParts = $this->splitPipeDelimited($paths);
        $idParts = $this->splitPipeDelimited($ids);

        $lineage = [];

        foreach ($rankParts as $index => $rank) {
            $lineage[] = [
                'rank' => $rank,
                'name' => $pathParts[$index] ?? '',
                'id' => $idParts[$index] ?? null,
            ];
        }

        return array_values(array_filter(
            $lineage,
            fn (array $item): bool => $item['name'] !== '',
        ));
    }

    /**
     * @param  list<array{rank: string, name: string, id: string|null}>  $lineage
     * @return list<array{rank: string, name: string, id: string|null}>
     */
    public function filterDisplayRanks(array $lineage): array
    {
        return array_values(array_filter(
            $lineage,
            fn (array $item): bool => in_array(strtolower($item['rank']), self::DISPLAY_RANKS, true),
        ));
    }

    /**
     * @param  list<array{rank: string, name: string, id: string|null}>  $lineage
     */
    public function resolveBiologicalGroup(array $lineage): ?string
    {
        foreach ($lineage as $item) {
            if (strtolower($item['rank']) === 'kingdom' && $item['name'] !== '') {
                return $item['name'];
            }
        }

        foreach ($lineage as $item) {
            if (in_array(strtolower($item['rank']), ['domain', 'superkingdom'], true) && $item['name'] !== '') {
                return $item['name'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $bestResult
     * @return list<array{label: string, url: string}>
     */
    private function buildReferences(array $bestResult): array
    {
        $references = [];

        $outlink = $bestResult['outlink'] ?? null;
        $source = $bestResult['dataSourceTitleShort'] ?? 'Data source';

        if (is_string($outlink) && $outlink !== '') {
            $references[] = [
                'label' => $source,
                'url' => $outlink,
            ];
        }

        $canonical = $bestResult['matchedCanonicalSimple'] ?? null;

        if (is_string($canonical) && $canonical !== '') {
            $references[] = [
                'label' => 'GBIF search',
                'url' => 'https://www.gbif.org/species/search?q='.rawurlencode($canonical),
            ];
        }

        return $references;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function appendNcbiReference(array $references, ?string $ncbiIri): array
    {
        $taxId = $this->extractNcbiTaxId($ncbiIri);

        if ($taxId === null) {
            return $references;
        }

        array_unshift($references, [
            'label' => 'NCBI Taxonomy',
            'url' => 'https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id='.$taxId,
        ]);

        return $references;
    }

    public function extractNcbiTaxId(?string $iri): ?string
    {
        if ($iri === null || $iri === '') {
            return null;
        }

        $decoded = urldecode($iri);

        if (preg_match('/NCBITaxon_(\d+)/', $decoded, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function splitPipeDelimited(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode('|', rtrim($value, '|'))),
            fn (string $part): bool => $part !== '',
        ));
    }
}
