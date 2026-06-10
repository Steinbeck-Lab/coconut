<?php

namespace Tests\Unit;

use App\Models\Organism;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganismTaxonomyBackfillScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_needing_taxonomy_enrichment_only_uses_null_safe_sql_conditions(): void
    {
        $exact = Organism::query()->create([
            'name' => 'Exact organism',
            'molecule_count' => 1,
            'taxonomy' => ['match_type' => 'Exact'],
            'taxonomy_fetched_at' => now(),
            'iri' => urlencode('http://purl.obolibrary.org/obo/NCBITaxon_1'),
        ]);

        $missingTaxonomy = Organism::query()->create([
            'name' => 'Missing taxonomy',
            'molecule_count' => 2,
            'iri' => urlencode('http://purl.obolibrary.org/obo/NCBITaxon_2'),
        ]);

        $fuzzyTaxonomy = Organism::query()->create([
            'name' => 'Fuzzy taxonomy',
            'molecule_count' => 3,
            'taxonomy' => ['match_type' => 'Fuzzy'],
            'taxonomy_fetched_at' => now(),
        ]);

        $ids = Organism::query()->needingTaxonomyEnrichment()->pluck('id')->all();

        $this->assertContains($missingTaxonomy->id, $ids);
        $this->assertNotContains($exact->id, $ids);
        $this->assertNotContains($fuzzyTaxonomy->id, $ids);
    }

    public function test_has_exact_taxonomy_enrichment_detects_non_exact_profiles_in_php(): void
    {
        $fuzzy = Organism::query()->create([
            'name' => 'Fuzzy taxonomy',
            'taxonomy' => ['match_type' => 'Fuzzy'],
            'taxonomy_fetched_at' => now(),
        ]);

        $exact = Organism::query()->create([
            'name' => 'Exact taxonomy',
            'taxonomy' => ['match_type' => 'Exact'],
            'taxonomy_fetched_at' => now(),
        ]);

        $this->assertFalse($fuzzy->hasExactTaxonomyEnrichment());
        $this->assertTrue($exact->hasExactTaxonomyEnrichment());
    }
}
