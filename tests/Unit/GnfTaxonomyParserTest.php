<?php

namespace Tests\Unit;

use App\Services\OrganismTaxonomy\GnfTaxonomyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GnfTaxonomyParserTest extends TestCase
{
    private GnfTaxonomyParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new GnfTaxonomyParser;
    }

    public function test_parse_verifier_batch_response_maps_each_name(): void
    {
        $response = [
            'names' => [
                [
                    'name' => 'Homo sapiens',
                    'matchType' => 'Exact',
                    'bestResult' => [
                        'dataSourceTitleShort' => 'Catalogue of Life',
                        'matchedCanonicalSimple' => 'Homo sapiens',
                        'classificationPath' => 'Animalia|Homo sapiens',
                        'classificationRanks' => 'kingdom|species',
                        'classificationIds' => '1|2',
                    ],
                ],
                [
                    'name' => 'Aspergillus fumigatus',
                    'matchType' => 'Exact',
                    'bestResult' => [
                        'dataSourceTitleShort' => 'Catalogue of Life',
                        'matchedCanonicalSimple' => 'Aspergillus fumigatus',
                        'classificationPath' => 'Fungi|Aspergillus fumigatus',
                        'classificationRanks' => 'kingdom|species',
                        'classificationIds' => '3|4',
                    ],
                ],
            ],
        ];

        $profiles = $this->parser->parseVerifierBatchResponse($response, [
            'HOMO SAPIENS',
            'Aspergillus fumigatus',
            'Missing species',
        ]);

        $this->assertNotNull($profiles['HOMO SAPIENS']);
        $this->assertSame('Homo sapiens', $profiles['HOMO SAPIENS']['canonical_name']);
        $this->assertNotNull($profiles['Aspergillus fumigatus']);
        $this->assertNull($profiles['Missing species']);
    }

    public function test_parse_verifier_response_builds_researcher_friendly_profile(): void
    {
        $profile = $this->parser->parseVerifierResponse([
            'names' => [[
                'matchType' => 'Exact',
                'bestResult' => [
                    'dataSourceId' => 1,
                    'dataSourceTitleShort' => 'Catalogue of Life',
                    'outlink' => 'https://www.catalogueoflife.org/data/taxon/4VQWK',
                    'matchedCanonicalSimple' => 'Sclerotinia sclerotiorum',
                    'matchedName' => 'Sclerotinia sclerotiorum (Lib.) de Bary',
                    'taxonomicStatus' => 'Accepted',
                    'isSynonym' => false,
                    'classificationPath' => 'Eukaryota|Fungi|Ascomycota|Leotiomycetes|Helotiales|Sclerotiniaceae|Sclerotinia|Sclerotinia sclerotiorum',
                    'classificationRanks' => 'domain|kingdom|phylum|class|order|family|genus|species',
                    'classificationIds' => 'CS5HF|F|SM|DQ|3BV|G4R|63SSZ|4VQWK',
                ],
            ]],
        ], 'Sclerotinia sclerotiorum');

        $this->assertNotNull($profile);
        $this->assertSame('Sclerotinia sclerotiorum', $profile['canonical_name']);
        $this->assertSame('Fungi', $profile['biological_group']);
        $this->assertSame('Exact', $profile['match_type']);
        $this->assertCount(7, $profile['ranks']);
        $this->assertSame('Catalogue of Life', $profile['references'][0]['label']);
    }

    #[DataProvider('ncbiTaxIdProvider')]
    public function test_extract_ncbi_tax_id_from_ols_iri(?string $iri, ?string $expected): void
    {
        $this->assertSame($expected, $this->parser->extractNcbiTaxId($iri));
    }

    public static function ncbiTaxIdProvider(): array
    {
        return [
            'encoded iri' => [urlencode('http://purl.obolibrary.org/obo/NCBITaxon_5180'), '5180'],
            'plain iri' => ['http://purl.obolibrary.org/obo/NCBITaxon_9606', '9606'],
            'missing' => [null, null],
        ];
    }
}
