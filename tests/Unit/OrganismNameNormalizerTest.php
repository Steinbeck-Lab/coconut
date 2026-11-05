<?php

namespace Tests\Unit;

use App\Services\OrganismTaxonomy\OrganismNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganismNameNormalizerTest extends TestCase
{
    private OrganismNameNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new OrganismNameNormalizer;
    }

    #[DataProvider('lookupNameProvider')]
    public function test_normalize_for_lookup_formats_scientific_names(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer->normalizeForLookup($input));
    }

    public static function lookupNameProvider(): array
    {
        return [
            'all caps species' => ['HOMO SAPIENS', 'Homo sapiens'],
            'extra whitespace' => ['  Aspergillus   fumigatus  ', 'Aspergillus fumigatus'],
            'single genus' => ['eucalyptus', 'Eucalyptus'],
            'empty string' => ['', ''],
        ];
    }

    public function test_first_token_returns_leading_word(): void
    {
        $this->assertSame('Homo', $this->normalizer->firstToken('HOMO SAPIENS'));
        $this->assertNull($this->normalizer->firstToken('   '));
    }
}
