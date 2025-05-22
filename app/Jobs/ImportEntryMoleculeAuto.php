<?php

namespace App\Jobs;

use App\Models\Molecule;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportEntryMoleculeAuto implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entry;

    /**
     * Create a new job instance.
     */
    public function __construct($entry)
    {
        $this->entry = $entry;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->entry->status == 'PASSED') {
            $molecule = null;
            if ($this->entry->has_stereocenters) {
                $data = $this->getRepresentations('parent');
                $parent = $this->firstOrCreateMolecule($data['canonical_smiles'], $data['standard_inchi']);
                if ($parent->wasRecentlyCreated) {
                    $parent->is_parent = true;
                    $parent->is_placeholder = true;
                    $parent->variants_count += $parent->variants_count;
                    $parent = $this->assignData($parent, $data);
                    $parent->save();
                }
                $this->attachCollection($parent);

                $data = $this->getRepresentations('standardized');
                if ($data['has_stereo_defined']) {
                    $molecule = $this->firstOrCreateMolecule($data['canonical_smiles'], $data['standard_inchi']);
                    if ($molecule->wasRecentlyCreated) {
                        $molecule->status = 'DRAFT';
                        $molecule->has_stereo = true;
                        $molecule->parent_id = $parent->id;
                        $parent->has_variants = true;
                        $parent->save();
                        $molecule = $this->assignData($molecule, $data);
                        $molecule->save();
                    }
                    $this->entry->molecule_id = $molecule->id;
                    $this->entry->save();

                    $this->attachCollection($molecule);
                } else {
                    $this->entry->molecule_id = $parent->id;
                    $this->entry->save();

                    $this->attachCollection($parent);
                }
            } else {
                $data = $this->getRepresentations('standardized');
                $molecule = $this->firstOrCreateMolecule($data['canonical_smiles'], $data['standard_inchi']);
                if ($molecule->wasRecentlyCreated) {
                    $molecule = $this->assignData($molecule, $data);
                    $molecule->save();
                }
                $molecule->is_placeholder = false;
                $molecule->save();
                $this->entry->molecule_id = $molecule->id;
                $this->entry->save();
                $this->attachCollection($molecule);
            }

            $this->entry->status = 'AUTOCURATION';
            $this->entry->save();
        }
    }

    public function firstOrCreateMolecule($canonical_smiles, $standard_inchi)
    {
        $mol = Molecule::firstOrCreate(['standard_inchi' => $standard_inchi]);
        if (! $mol->wasRecentlyCreated) {
            if ($mol->canonical_smiles != $canonical_smiles) {
                $mol->is_tautomer = true;
                $mol->save();

                $_mol = Molecule::firstOrCreate(['standard_inchi' => $standard_inchi, 'canonical_smiles' => $canonical_smiles]);
                $_mol->is_tautomer = true;
                $_mol->save();

                $relatedMols = Molecule::where('standard_inchi', $standard_inchi)->get();
                foreach ($relatedMols as $molecule) {
                    // Get all molecules except the current one
                    $relatedIds = $relatedMols->where('id', '!=', $molecule->id)
                        ->pluck('id')
                        ->toArray();

                    // Establish tautomer relationships
                    $molecule->related()->syncWithPivotValues($relatedIds, ['type' => 'tautomers'], false);
                }

                return $_mol;
            }
        }

        return $mol;
    }

    public function getRepresentations($type)
    {
        $data = json_decode($this->entry->cm_data, true);
        $mol_data = $data[$type]['representations'];
        if ($type != 'parent') {
            $mol_data['has_stereo_defined'] = $data[$type]['has_stereo_defined'];
        }

        return $mol_data;
    }

    /**
     * Attach collection to molecule.
     *
     * @param  mixed  $molecule
     * @return void
     */
    public function attachCollection($molecule)
    {
        try {
            $collection_exists = $molecule->collections()->where('collections.id', $this->entry->collection_id)->exists();
            if ($collection_exists) {
                $collection = $molecule->collections()->where('collections.id', $this->entry->collection_id)->first();
                $molecule->collections()->syncWithoutDetaching([
                    $this->entry->collection_id => [
                        'url' => $collection->pivot->url.'|'.$this->entry->link,
                        'reference' => $collection->pivot->reference.'|'.$this->entry->reference_id,
                        'mol_filename' => $collection->pivot->mol_filename.'|'.$this->entry->mol_filename,
                        'structural_comments' => $collection->pivot->structural_comments.'|'.$this->entry->structural_comments,
                    ],
                ]);
            } else {
                $molecule->collections()->attach([
                    $this->entry->collection_id => [
                        'url' => $this->entry->link,
                        'reference' => $this->entry->reference_id,
                        'mol_filename' => $this->entry->mol_filename,
                        'structural_comments' => $this->entry->structural_comments,
                    ],
                ]);
            }
        } catch (QueryException $e) {
            if ($this->isUniqueViolationException($e)) {
                // $this->attachCollection($molecule);
            }
        }
    }

    /**
     * Check if the exception is a unique violation.
     *
     * @return bool
     */
    private function isUniqueViolationException(QueryException $e)
    {
        // Check if the SQLSTATE is 23505, which corresponds to a unique violation error
        return $e->getCode() == '23505';
    }

    /**
     * Assign data to the model.
     *
     * @param  mixed  $model
     * @param  array  $data
     * @return mixed
     */
    public function assignData($model, $data)
    {
        $model['standard_inchi'] = $data['standard_inchi'];
        $model['standard_inchi_key'] = $data['standard_inchikey'];
        $model['canonical_smiles'] = $data['canonical_smiles'];

        return $model;
    }
}
