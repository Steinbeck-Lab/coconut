<?php

namespace App\Services\OrganismTaxonomy;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class NcbiTaxonomyNameResolver
{
    private Client $client;

    public function __construct(
        private readonly GnfTaxonomyParser $taxonomyParser = new GnfTaxonomyParser,
        private readonly OrganismNameNormalizer $nameNormalizer = new OrganismNameNormalizer,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client([
            'base_uri' => 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/',
            'timeout' => (int) config('services.organism_taxonomy.http_timeout', 30),
        ]);
    }

    public function scientificNameFromIri(?string $iri): ?string
    {
        $taxId = $this->taxonomyParser->extractNcbiTaxId($iri);

        if ($taxId === null) {
            return null;
        }

        return $this->scientificNameForTaxId($taxId);
    }

    public function scientificNameForTaxId(string $taxId): ?string
    {
        $cacheKey = 'organism_taxonomy.ncbi_scientific_name.'.$taxId;

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($taxId): ?string {
            try {
                $response = $this->client->get('esummary.fcgi', [
                    'query' => [
                        'db' => 'taxonomy',
                        'id' => $taxId,
                        'retmode' => 'json',
                    ],
                ]);

                $payload = json_decode((string) $response->getBody(), true);
                $entry = $payload['result'][$taxId] ?? null;

                if (! is_array($entry)) {
                    return null;
                }

                $scientificName = trim((string) ($entry['scientificname'] ?? ''));

                if ($scientificName === '') {
                    return null;
                }

                return $this->nameNormalizer->normalizeForLookup($scientificName);
            } catch (Throwable $e) {
                Log::warning('NCBI taxonomy name lookup failed', [
                    'tax_id' => $taxId,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }
}
