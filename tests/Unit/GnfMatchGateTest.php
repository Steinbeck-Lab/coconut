<?php

namespace Tests\Unit;

use App\Services\OrganismTaxonomy\GnfMatchGate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GnfMatchGateTest extends TestCase
{
    #[DataProvider('exactProfileProvider')]
    public function test_accepts_only_exact_gnf_profiles_by_default(array $profile, string $lookup, bool $expected): void
    {
        $gate = new GnfMatchGate(requireExactMatch: true);

        $this->assertSame($expected, $gate->acceptsTaxonomyProfile($profile, $lookup));
    }

    public static function exactProfileProvider(): array
    {
        return [
            'exact canonical' => [
                [
                    'match_type' => 'Exact',
                    'canonical_name' => 'Homo sapiens',
                    'edit_distance' => 0,
                ],
                'HOMO SAPIENS',
                true,
            ],
            'fuzzy rejected' => [
                [
                    'match_type' => 'Fuzzy',
                    'canonical_name' => 'Homo sapiens',
                    'edit_distance' => 1,
                ],
                'Homo sapiens',
                false,
            ],
            'partial exact rejected' => [
                [
                    'match_type' => 'PartialExact',
                    'canonical_name' => 'Homo sapiens',
                ],
                'Homo sapiens',
                false,
            ],
            'exact with non-zero edit distance rejected' => [
                [
                    'match_type' => 'Exact',
                    'canonical_name' => 'Homo sapiens',
                    'edit_distance' => 2,
                ],
                'Homo sapiens',
                false,
            ],
        ];
    }

    public function test_allows_fuzzy_matches_when_configured(): void
    {
        $gate = new GnfMatchGate(requireExactMatch: false);

        $this->assertTrue($gate->acceptsMatchType('Fuzzy'));
        $this->assertTrue($gate->acceptsMatchType('PartialExact'));
    }
}
