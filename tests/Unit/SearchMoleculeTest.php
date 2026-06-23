<?php

namespace Tests\Unit;

use App\Actions\Coconut\SearchMolecule;
use App\Models\Molecule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchMoleculeTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_search_includes_parent_without_variants_when_not_placeholder(): void
    {
        Molecule::create([
            'identifier' => 'CNP9999001.0',
            'name' => 'Mirabilin B',
            'synonyms' => ['Mirabilin B'],
            'is_parent' => true,
            'has_variants' => false,
            'is_placeholder' => false,
            'active' => true,
        ]);

        [$results] = app(SearchMolecule::class)->query('Mirabilin B', 20, null, null, null, 1, 'all');

        $this->assertSame(1, $results->total());
        $this->assertSame('CNP9999001.0', $results->items()[0]->identifier);
    }

    public function test_text_search_excludes_placeholder_parents(): void
    {
        Molecule::create([
            'identifier' => 'CNP9999002.0',
            'name' => 'Mirabilin B',
            'is_parent' => true,
            'has_variants' => true,
            'is_placeholder' => true,
            'active' => true,
        ]);

        [$results] = app(SearchMolecule::class)->query('Mirabilin B', 20, null, null, null, 1, 'all');

        $this->assertSame(0, $results->total());
    }

    public function test_text_search_ranks_exact_name_match_before_synonym_match(): void
    {
        Molecule::create([
            'identifier' => 'CNP9999003.0',
            'name' => 'Mirabilin B',
            'is_placeholder' => false,
            'active' => true,
        ]);

        Molecule::create([
            'identifier' => 'CNP9999004.0',
            'name' => null,
            'synonyms' => ['Mirabilin B'],
            'is_placeholder' => false,
            'active' => true,
        ]);

        [$results] = app(SearchMolecule::class)->query('Mirabilin B', 20, null, null, null, 1, 'all');

        $this->assertSame(2, $results->total());
        $this->assertSame('CNP9999003.0', $results->items()[0]->identifier);
        $this->assertSame('CNP9999004.0', $results->items()[1]->identifier);
    }
}
