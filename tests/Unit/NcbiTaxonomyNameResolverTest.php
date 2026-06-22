<?php

namespace Tests\Unit;

use App\Services\OrganismTaxonomy\NcbiTaxonomyNameResolver;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NcbiTaxonomyNameResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_scientific_name_from_ncbi_iri(): void
    {
        $resolver = $this->makeResolver([
            new Response(200, [], json_encode([
                'result' => [
                    '55957' => [
                        'scientificname' => 'Lindera',
                        'rank' => 'genus',
                    ],
                ],
            ])),
        ]);

        $name = $resolver->scientificNameFromIri('http://purl.obolibrary.org/obo/NCBITaxon_55957');

        $this->assertSame('Lindera', $name);
    }

    public function test_returns_null_for_non_ncbi_iri(): void
    {
        $resolver = $this->makeResolver([]);

        $this->assertNull($resolver->scientificNameFromIri('https://www.catalogueoflife.org/data/taxon/5Z7HS'));
    }

    /**
     * @param  list<Response>  $responses
     */
    private function makeResolver(array $responses): NcbiTaxonomyNameResolver
    {
        $mock = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new NcbiTaxonomyNameResolver(client: $client);
    }
}
