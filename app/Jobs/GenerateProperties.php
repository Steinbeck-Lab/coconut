<?php

namespace App\Jobs;

use App\Models\Properties;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateProperties implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 45;

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
        try {
            $canonical_smiles = $this->molecule->canonical_smiles;
            $API_URL = env('API_URL', 'https://api.cheminf.studio/latest/');
            $ENDPOINT = $API_URL.'chem/descriptors?smiles='.urlencode($canonical_smiles).'&format=json&toolkit=rdkit';

            $response = Http::timeout(30)->get($ENDPOINT);
            if ($response->successful()) {
                $descriptors = $response->json();
                $descriptors['standard_inchi'] = $this->molecule->standard_inchi;
                $this->attachProperties($descriptors, $this->molecule->id);
                updateCurationStatus($this->molecule->id, 'generate-properties', 'completed');
            } else {
                $error = 'Failed to get properties from API: '.$response->status();
                Log::error($error.' - '.$canonical_smiles);
                updateCurationStatus($this->molecule->id, 'generate-properties', 'failed', $error);

                // Dispatch event for notification handling
                // \App\Events\ImportPipelineJobFailed::dispatch(
                //     self::class,
                //     new \Exception($error),
                //     [
                //         'molecule_id' => $this->molecule->id,
                //         'canonical_smiles' => $canonical_smiles ?? 'Unknown',
                //         'step' => 'generate-properties',
                //     ],
                //     $this->batch()?->id
                // );
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            $error = 'An unexpected exception occurred: '.$e->getMessage();
            Log::error($error.' - '.$canonical_smiles);
            updateCurationStatus($this->molecule->id, 'generate-properties', 'failed', $error);

            // Dispatch event for notification handling
            // \App\Events\ImportPipelineJobFailed::dispatch(
            //     self::class,
            //     $e,
            //     [
            //         'molecule_id' => $this->molecule->id,
            //         'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
            //         'step' => 'generate-properties',
            //     ],
            //     $this->batch()?->id
            // );

            throw $e;
        }
    }

    public function attachProperties($descriptors, $id)
    {
        $properties = Properties::firstOrCreate(['molecule_id' => $id]);

        $properties->total_atom_count = $descriptors['atom_count'] ?? 0;
        $properties->heavy_atom_count = $descriptors['heavy_atom_count'] ?? 0;
        $properties->molecular_weight = $descriptors['molecular_weight'] ?? 0;
        $properties->molecular_formula = preg_split('#/#', $descriptors['standard_inchi'] ?? '')[1] ?? '';
        $properties->exact_molecular_weight = $descriptors['exact_molecular_weight'] ?? 0;
        $properties->alogp = $descriptors['alogp'] ?? 0;
        $properties->rotatable_bond_count = $descriptors['rotatable_bond_count'] ?? 0;
        $properties->topological_polar_surface_area = $descriptors['topological_polar_surface_area'] ?? 0;
        $properties->hydrogen_bond_acceptors = $descriptors['hydrogen_bond_acceptors'] ?? 0;
        $properties->hydrogen_bond_donors = $descriptors['hydrogen_bond_donors'] ?? 0;
        $properties->hydrogen_bond_acceptors_lipinski = $descriptors['hydrogen_bond_acceptors_lipinski'] ?? 0;
        $properties->hydrogen_bond_donors_lipinski = $descriptors['hydrogen_bond_donors_lipinski'] ?? 0;
        $properties->lipinski_rule_of_five_violations = $descriptors['lipinski_rule_of_five_violations'] ?? 0;
        $properties->aromatic_rings_count = $descriptors['aromatic_rings_count'] ?? 0;
        $properties->qed_drug_likeliness = $descriptors['qed_drug_likeliness'] ?? 0;
        $properties->formal_charge = $descriptors['formal_charge'] ?? 0;
        $properties->fractioncsp3 = $descriptors['fractioncsp3'] ?? 0;
        $properties->number_of_minimal_rings = $descriptors['number_of_minimal_rings'] ?? 0;
        $properties->van_der_walls_volume = $descriptors['van_der_waals_volume'] == 'None' ? 0 : $descriptors['van_der_waals_volume'];
        $properties->contains_ring_sugars = $descriptors['circular_sugars'] ?? 0;
        $properties->contains_linear_sugars = $descriptors['linear_sugars'] ?? 0;
        $properties->murcko_framework = $descriptors['murcko_framework'] ?? '';
        $properties->np_likeness = $descriptors['nplikeness'] ?? 0;
        $properties->molecule_id = $id;
        $properties->save();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('999999'))
                ->releaseAfter(30)      // Release lock if job fails/times out
                ->expireAfter(180)      // Maximum lock duration
                ->dontRelease()         // Don't retry if can't acquire lock
                ->shared(),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $isTimeout = str_contains($exception->getMessage(), 'Job has timed out') ||
            str_contains($exception->getMessage(), 'Maximum execution time') ||
            $exception instanceof \Illuminate\Queue\MaxAttemptsExceededException;

        $errorMessage = $isTimeout ? 'Job timed out after 45 seconds' : $exception->getMessage();

        Log::error("GenerateProperties job failed for molecule {$this->molecule->id}: {$errorMessage}");
        updateCurationStatus($this->molecule->id, 'generate-properties', 'failed', $errorMessage);

        // Dispatch event for notification handling
        \App\Events\ImportPipelineJobFailed::dispatch(
            self::class,
            $exception,
            [
                'molecule_id' => $this->molecule->id,
                'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
                'step' => 'generate-properties',
                'timeout' => $isTimeout,
            ],
            $this->batch()?->id
        );
    }
}
