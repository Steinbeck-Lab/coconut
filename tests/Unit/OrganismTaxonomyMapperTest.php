<?php

namespace Tests\Unit;

use App\Services\OrganismTaxonomy\OrganismTaxonomyMapper;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganismTaxonomyMapperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.organism_taxonomy.throttle_ms' => 0,
            'services.organism_taxonomy.ols_base_uri' => 'https://www.ebi.ac.uk/ols4/api/v2/',
            'services.organism_taxonomy.gnf_finder_url' => 'https://finder.globalnames.org/api/v1/find',
            'services.organism_taxonomy.gnf_verifier_url' => 'https://verifier.globalnames.org/api/v1/verifications',
        ]);
    }

    public function test_map_returns_ols_species_match_with_correct_rank(): void
    {
        $mapper = $this->makeMapper([
            new Response(200, [], json_encode([
                'names' => [[
                    'matchType' => 'Exact',
                    'bestResult' => [
                        'dataSourceTitleShort' => 'Catalogue of Life',
                        'outlink' => 'https://www.catalogueoflife.org/data/taxon/test',
                        'matchedCanonicalSimple' => 'Homo sapiens',
                        'classificationRanks' => 'kingdom|species',
                        'classificationPath' => 'Animalia|Homo sapiens',
                        'classificationIds' => '1|2',
                    ],
                ]],
            ])),
            new Response(200, [], json_encode([
                'elements' => [[
                    'iri' => 'http://purl.obolibrary.org/obo/NCBITaxon_9606',
                    'ontologyId' => 'ncbitaxon',
                    'isObsolete' => false,
                    'http://purl.obolibrary.org/obo/ncbitaxon#has_rank' => 'http://purl.obolibrary.org/obo/NCBITaxon_species',
                ]],
            ])),
        ]);

        $result = $mapper->map('HOMO SAPIENS');

        $this->assertTrue($result->isMapped());
        $this->assertSame('species', $result->rank);
        $this->assertSame('ols', $result->source);
        $this->assertStringContainsString('NCBITaxon_9606', urldecode($result->iri ?? ''));
        $this->assertNotNull($result->taxonomy);
        $this->assertSame('Homo sapiens', $result->taxonomy['canonical_name']);
    }

    public function test_map_falls_back_to_gnf_when_ols_has_no_match(): void
    {
        $mapper = $this->makeMapper([
            new Response(200, [], json_encode(['names' => []])),
            new Response(200, [], json_encode(['elements' => []])),
            new Response(200, [], json_encode(['elements' => []])),
            new Response(200, [], json_encode([
                'names' => [[
                    'verification' => [
                        'matchType' => 'Exact',
                        'bestResult' => [
                            'dataSourceTitleShort' => 'WoRMS',
                            'outlink' => 'https://www.marinespecies.org/aphia.php?p=taxdetails&id=1',
                            'matchedCanonicalSimple' => 'Some obscure species',
                            'classificationRanks' => 'kingdom|species',
                            'classificationPath' => 'Animalia|Some obscure species',
                            'classificationIds' => '1|2',
                        ],
                    ],
                ]],
            ])),
        ]);

        $result = $mapper->map('Some obscure species');

        $this->assertTrue($result->isMapped());
        $this->assertSame('gnf', $result->source);
        $this->assertSame('Exact', $result->matchType);
        $this->assertSame('species', $result->rank);
    }

    public function test_parse_gnf_response_rejects_fuzzy_matches_by_default(): void
    {
        $mapper = new OrganismTaxonomyMapper;

        $result = $mapper->parseGnfResponse([
            'names' => [[
                'verification' => [
                    'matchType' => 'Fuzzy',
                    'bestResult' => [
                        'dataSourceTitleShort' => 'Catalogue of Life',
                        'outlink' => 'https://www.catalogueoflife.org/data/taxon/test',
                        'matchedCanonicalSimple' => 'Homo sapiens',
                        'classificationRanks' => 'kingdom|species',
                        'classificationPath' => 'Animalia|Homo sapiens',
                        'classificationIds' => '1|2',
                        'editDistance' => 1,
                    ],
                ],
            ]],
        ], 'Homo sapiens');

        $this->assertNull($result);
    }

    public function test_map_uses_family_rank_when_ols_family_match_is_found(): void
    {
        $mapper = $this->makeMapper([
            new Response(200, [], json_encode(['names' => []])),
            new Response(200, [], json_encode([
                'elements' => [[
                    'iri' => 'http://purl.obolibrary.org/obo/NCBITaxon_4751',
                    'ontologyId' => 'ncbitaxon',
                    'isObsolete' => 'false',
                    'http://purl.obolibrary.org/obo/ncbitaxon#has_rank' => 'http://purl.obolibrary.org/obo/NCBITaxon_family',
                ]],
            ])),
        ]);

        $result = $mapper->map('Fungi');

        $this->assertTrue($result->isMapped());
        $this->assertSame('family', $result->rank);
        $this->assertSame('ols', $result->source);
    }

    #[DataProvider('gnfPartialMatchProvider')]
    public function test_parse_gnf_response_handles_partial_matches(array $payload, bool $shouldMap, ?string $expectedRank): void
    {
        $mapper = new OrganismTaxonomyMapper;

        $result = $mapper->parseGnfResponse($payload, 'Test organism');

        if (! $shouldMap) {
            $this->assertNull($result);

            return;
        }

        $this->assertNotNull($result);
        $this->assertStringContainsString($expectedRank, (string) $result->rank);
        $this->assertSame('gnf', $result->source);
    }

    public static function gnfPartialMatchProvider(): array
    {
        return [
            'partial with parent hierarchy' => [
                [
                    'names' => [[
                        'verification' => [
                            'matchType' => 'PartialExact',
                            'bestResult' => [
                                'dataSourceTitleShort' => 'IRMNG',
                                'matchedCanonicalSimple' => 'Homo sapiens',
                                'classificationRanks' => 'kingdom|phylum|genus|species',
                                'classificationPath' => 'Animalia|Chordata|Homo|Homo sapiens',
                                'classificationIds' => '1|2|3|4',
                            ],
                        ],
                    ]],
                ],
                false,
                null,
            ],
            'partial without enough hierarchy' => [
                [
                    'names' => [[
                        'verification' => [
                            'matchType' => 'PartialExact',
                            'bestResult' => [
                                'dataSourceTitleShort' => 'IRMNG',
                                'classificationRanks' => 'species',
                                'classificationPath' => 'Homo sapiens',
                                'classificationIds' => '4',
                            ],
                        ],
                    ]],
                ],
                false,
                null,
            ],
        ];
    }

    /**
     * @param  list<Response>  $responses
     */
    private function makeMapper(array $responses): OrganismTaxonomyMapper
    {
        $mock = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new OrganismTaxonomyMapper(olsClient: $client, gnfClient: $client);
    }
}
