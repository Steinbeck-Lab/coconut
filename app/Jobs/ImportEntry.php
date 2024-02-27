<?php

namespace App\Jobs;

use App\Models\Molecule;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportEntry implements ShouldBeUnique, ShouldQueue
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
            if ($this->entry->has_stereocenters) {
                $data = $this->getRepresentations('parent');
                $parent = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi']]);
                $parent->is_parent = true;
                $parent->has_variants = true;
                $parent->identifier = $this->entry->coconut_id;
                $parent->variants_count += $parent->variants_count;
                $parent = $this->assignData($parent, $data);
                $parent->save();

                $data = $this->getRepresentations('standardized');
                $molecule = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi']]);
                $molecule->has_stereo = true;
                $molecule->parent_id = $parent->id;
                // $molecule->identifier = $this->entry->coconut_id;
                $parent = $this->assignData($molecule, $data);
                $molecule->save();
            } else {
                $data = $this->getRepresentations('standardized');
                $molecule = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi']]);
                $parent = $this->assignData($molecule, $data);
                $molecule->identifier = $this->entry->coconut_id;
                $molecule->save();
            }
        }
    }

    public function getRepresentations($type)
    {
        $data = json_decode($this->entry->cm_data, true);
        $data = $data[$type]['representations'];

        return $data;
    }

    public function assignData($model, $data)
    {
        $model['standard_inchi'] = $data['standard_inchi'];
        $model['standard_inchi_key'] = $data['standard_inchikey'];
        $model['canonical_smiles'] = $data['canonical_smiles'];

        return $model;
    }
}
