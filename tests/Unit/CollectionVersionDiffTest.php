<?php

namespace Tests\Unit;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Molecule;
use App\Services\CollectionVersioning\CollectionVersionDiff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CollectionVersionDiffTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_compares_standardized_canonical_smiles(): void
    {
        $old = Collection::factory()->create([
            'version' => 1,
            'is_latest' => true,
            'identifier' => 'CNPC_TEST_1',
        ]);
        $new = Collection::factory()->create([
            'parent_collection_id' => $old->id,
            'version' => 2,
            'is_latest' => false,
            'identifier' => 'CNPC_TEST_1',
        ]);

        $molA = Molecule::query()->create(['canonical_smiles' => 'AAA', 'standard_inchi' => 'InChI-A']);
        $molB = Molecule::query()->create(['canonical_smiles' => 'BBB', 'standard_inchi' => 'InChI-B']);

        foreach ([
            ['collection_id' => $old->id, 'molecule_id' => $molA->id, 'smiles' => 'AAA'],
            ['collection_id' => $old->id, 'molecule_id' => $molB->id, 'smiles' => 'BBB'],
            ['collection_id' => $new->id, 'molecule_id' => null, 'smiles' => 'BBB', 'status' => 'PASSED'],
            ['collection_id' => $new->id, 'molecule_id' => null, 'smiles' => 'CCC', 'status' => 'PASSED'],
        ] as $row) {
            Entry::query()->create([
                'collection_id' => $row['collection_id'],
                'molecule_id' => $row['molecule_id'],
                'standardized_canonical_smiles' => $row['smiles'],
                'canonical_smiles' => $row['smiles'],
                'status' => $row['status'] ?? 'IMPORTED',
                'uuid' => (string) Str::uuid(),
            ]);
        }

        $diff = app(CollectionVersionDiff::class)->compare($old, $new);

        $this->assertTrue($diff->oldOnlySmilesToMoleculeId->has('AAA'));
        $this->assertTrue($diff->retainedSmilesToMoleculeId->has('BBB'));
        $this->assertTrue($diff->newOnlySmiles->contains('CCC'));
    }
}
