<?php

namespace App\Jobs;

use App\Models\Properties;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateProperties implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

    /**
     * Create a new job instance.
     */
    public function __construct($molecule)
    {
        $this->molecule = $molecule;
    }

    /**
     * Get a unique identifier for the queued job.
     */
    public function uniqueId(): string
    {
        return $this->molecule->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $canonical_smiles = $this->molecule->canonical_smiles;
        $API_URL = env('API_URL', 'https://api.cheminf.studio/latest/');
        $ENDPOINT = $API_URL.'chem/descriptors?smiles='.urlencode($canonical_smiles).'&format=json&toolkit=rdkit';
        try {
            $response = Http::timeout(600)->get($ENDPOINT);
            if ($response->successful()) {
                $descriptors = $response->json();
                $descriptors['standard_inchi'] = $this->molecule->standard_inchi;
                $this->attachProperties($descriptors, $this->molecule->id);
            }
        } catch (\Exception $e) {
            Log::error('An unexpected exception occurred: '.$e->getMessage().' - '.$canonical_smiles);
            $errors = [
                'An unexpected exception occurred' => $e->getMessage().' - '.$canonical_smiles,
            ];
        }
    }

    public function attachProperties($descriptors, $id)
    {
        $properties = Properties::firstOrCreate(['molecule_id' => $id]);
        $properties->total_atom_count = $descriptors['atom_count'];
        $properties->heavy_atom_count = $descriptors['heavy_atom_count'];
        $properties->molecular_weight = $descriptors['molecular_weight'];
        $properties->molecular_formula = $molecular_formula = preg_split('#/#', $descriptors['standard_inchi'])[1];
        $properties->exact_molecular_weight = $descriptors['exact_molecular_weight'];
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
        $properties->van_der_walls_volume = $descriptors['van_der_waals_volume'] == 'None' ? 0 : $descriptors['van_der_waals_volume'];
        $properties->contains_ring_sugars = $descriptors['circular_sugars'];
        $properties->contains_linear_sugars = $descriptors['linear_sugars'];
        $properties->murcko_framework = $descriptors['murko_framework'];
        $properties->np_likeness = $descriptors['nplikeness'];
        $properties->molecule_id = $id;
        $properties->save();
    }
}
