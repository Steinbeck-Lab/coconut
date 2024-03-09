<?php

namespace App\Jobs;

use App\Models\Molecule;
use App\Models\Properties;
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
                $parent = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi'], 'standard_inchi_key' => $data['standard_inchikey']]);
                $parent->is_parent = true;
                $parent->has_variants = true;
                $parent->identifier = $this->entry->coconut_id;
                $parent->variants_count += $parent->variants_count;
                $parent = $this->assignData($parent, $data);
                $parent->save();
                $this->attachProperties('parent', $parent);

                $data = $this->getRepresentations('standardized');
                $molecule = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi'], 'standard_inchi_key' => $data['standard_inchikey']]);
                $molecule->has_stereo = true;
                $molecule->parent_id = $parent->id;
                $parent->ticker = $parent->ticker + 1;
                $molecule->identifier = $this->entry->coconut_id.'.'.$parent->ticker;
                $parent = $this->assignData($molecule, $data);
                $parent->save();
                $molecule->save();
                $this->attachProperties('standardized', $molecule);
            } else {
                $data = $this->getRepresentations('standardized');
                $molecule = Molecule::firstOrCreate(['standard_inchi' => $data['standard_inchi'], 'standard_inchi_key' => $data['standard_inchikey']]);
                $parent = $this->assignData($molecule, $data);
                $molecule->identifier = $this->entry->coconut_id;
                $molecule->save();
                $this->attachProperties('standardized', $molecule);
            }
        }
    }

    public function getRepresentations($type)
    {
        $data = json_decode($this->entry->cm_data, true);
        $data = $data[$type]['representations'];

        return $data;
    }

    public function attachProperties($type, $model)
    {
        $data = json_decode($this->entry->cm_data, true);
        $descriptors = $data[$type]['descriptors'];
        $properties = Properties::firstOrCreate(['molecule_id' => $model->id]);
        $properties->total_atom_count = $descriptors['atom_count'];
        $properties->heavy_atom_count = $descriptors['heavy_atom_count'];
        $properties->molecular_weight = $descriptors['molecular_weight'];
        $properties->exact_molecular_weight = $descriptors['exactmolecular_weight'];
        $properties->alogp = $descriptors['alogp'];
        $properties->rotatable_bond_count = $descriptors['rotatable_bond_count'];
        $properties->topological_polar_surface_area = $descriptors['topological_polar_surface_area'];
        $properties->hydrogen_bond_acceptors = $descriptors['hydrogen_bond_acceptors'];
        $properties->hydrogen_bond_donors = $descriptors['hydrogen_bond_donors'];
        $properties->hydrogen_bond_acceptors_lipinski = $descriptors['hydrogen_bond_acceptors_lipinski'];
        $properties->hydrogen_bond_donors_lipinski = $descriptors['hydrogen_bond_donors_lipinski'];
        $properties->lipinski_rule_of_five_violations = $descriptors['lipinski_rule_of_five_violations'];
        $properties->aromatic_rings_count = $descriptors['aromatic_rings_count'];
        $properties->qed_drug_likeliness = $descriptors['qed_drug_likeliness'];
        $properties->formal_charge = $descriptors['formal_charge'];
        $properties->fractioncsp3 = $descriptors['fractioncsp3'];
        $properties->number_of_minimal_rings = $descriptors['number_of_minimal_rings'];
        $properties->van_der_walls_volume = $descriptors['van_der_walls_volume'] == 'None' ? 0 : $descriptors['van_der_walls_volume'];
        $properties->contains_ring_sugars = $descriptors['circular_sugars'];
        $properties->contains_linear_sugars = $descriptors['linear_sugars'];
        $properties->murko_framework = $descriptors['murko_framework'];
        $properties->np_likeness = $descriptors['nplikeness'];
        $properties->molecule_id = $model->id;
        $properties->save();
    }

    public function assignData($model, $data)
    {
        $model['standard_inchi'] = $data['standard_inchi'];
        $model['standard_inchi_key'] = $data['standard_inchikey'];
        $model['canonical_smiles'] = $data['canonical_smiles'];

        return $model;
    }
}
