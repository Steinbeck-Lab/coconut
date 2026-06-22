<?php

namespace Tests\Feature;

use App\Actions\Coconut\SearchMolecule;
use App\Models\Molecule;
use App\Models\Properties;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NpClassifierSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_np_pathway_filter_matches_any_stored_pathway(): void
    {
        $molecule = Molecule::create([
            'canonical_smiles' => 'CCO',
            'identifier' => 'CNP-TEST-001',
            'active' => true,
            'is_placeholder' => false,
        ]);

        Properties::create([
            'molecule_id' => $molecule->id,
            'np_classifier_pathway' => ['Amino acids and Peptides', 'Polyketides'],
        ]);

        $search = new SearchMolecule;
        [$resultsA] = $search->query(
            'np_pathway:amino-acids-and-peptides',
            20,
            'filters',
            null,
            null,
            1
        );

        $search = new SearchMolecule;
        [$resultsB] = $search->query(
            'np_pathway:polyketides',
            20,
            'filters',
            null,
            null,
            1
        );

        $identifiersA = collect($resultsA->items())->pluck('identifier')->all();
        $identifiersB = collect($resultsB->items())->pluck('identifier')->all();

        $this->assertContains($molecule->identifier, $identifiersA);
        $this->assertContains($molecule->identifier, $identifiersB);
    }
}
