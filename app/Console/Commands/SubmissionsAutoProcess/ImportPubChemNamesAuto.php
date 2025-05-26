<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportPubChemBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportPubChemNamesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-pubchem-data-auto {collection_id=65 : The ID of the collection to process} {--retry-failed : Include previously failed molecules} {--force : Retry processing of molecules with failed status only} {--trigger : Trigger subsequent commands in the processing chain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import PubChem data for molecules without names for a specific collection, excluding previously failed ones unless retry is specified';

    /**
     * The file where failed molecule IDs are stored
     *
     * @var string
     */
    protected $failedIdsFile = 'pubchem_failed_molecules.json';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $retryFailed = $this->option('retry-failed');
        $forceProcess = $this->option('force');
        $triggerNext = $this->option('trigger');

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }
        Log::info("Importing PubChem data for collection ID: {$collection_id}");

        // Load the list of failed molecule IDs
        $failedIds = $this->getFailedMoleculeIds();

        // Base query to find molecules needing PubChem data for specific collection
        $query = Molecule::select('molecules.id')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->where(function ($query) {
                $query->whereNull('molecules.name')
                    ->orWhere('molecules.name', '=', '');
            })
            ->whereNull('molecules.iupac_name')
            ->whereNull('molecules.synonyms')
            ->distinct();

        // If not forcing, exclude already processed molecules (completed OR failed)
        if (! $forceProcess) {
            $query->where(function ($q) {
                $q->whereNull('curation_status')
                    ->orWhereRaw('JSON_EXTRACT(curation_status, "$.import-pubchem-names.status") IS NULL')
                    ->orWhereRaw('JSON_EXTRACT(curation_status, "$.import-pubchem-names.status") NOT IN ("completed", "failed")');
            });
        } else {
            // If forcing, only process molecules with failed status
            $query->whereRaw('JSON_EXTRACT(curation_status, "$.import-pubchem-names.status") = "failed"');
        }

        // Exclude failed molecules unless retry is specified
        if (! $retryFailed && count($failedIds) > 0) {
            Log::info('Excluding '.count($failedIds).' previously failed molecules. Use --retry-failed to include them.');
            $query->whereNotIn('molecules.id', $failedIds);
        }

        $count = $query->count();
        if ($count === 0) {
            Log::info("No molecules found that require PubChem data import for collection {$collection_id}.");
            if ($triggerNext) {
                Artisan::call('coconut:generate-properties-auto', ['collection_id' => $collection_id, '--trigger' => true]);
            }

            return 0;
        }

        Log::info("Found {$count} molecules requiring PubChem data import for collection {$collection_id}");

        // Use chunk to process large sets of molecules
        $query->chunkById(10000, function ($mols) use ($collection_id, $triggerNext) {
            $moleculeCount = count($mols);
            Log::info("Processing batch of {$moleculeCount} molecules for collection {$collection_id}");

            // Record the processing attempt in curation status for each molecule
            foreach ($mols as $molecule) {
                updateCurationStatus($molecule->id, 'import-pubchem-names', 'processing');
            }

            // Prepare batch jobs
            $batchJobs = [];
            $batchJobs[] = new ImportPubChemBatch($mols->pluck('id')->toArray());

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) use ($collection_id, $triggerNext) {
                    Log::info("PubChem import batch completed successfully for collection {$collection_id}: ".$batch->id);
                    if ($triggerNext) {
                        Log::info("Calling GeneratePropertiesAuto after PubChem import batch for collection {$collection_id}");
                        Artisan::call('coconut:generate-properties-auto', ['collection_id' => $collection_id, '--trigger' => true]);
                    }
                })
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("PubChem import batch failed for collection {$collection_id}: ".$e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    // Handle final cleanup or logging
                })
                ->name("Import PubChem Auto Batch Collection {$collection_id}")
                ->allowFailures(true)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        Log::info("All PubChem import jobs dispatched for collection {$collection_id}!");
    }

    /**
     * Get the list of previously failed molecule IDs
     *
     * @return array
     */
    protected function getFailedMoleculeIds()
    {
        if (! Storage::exists($this->failedIdsFile)) {
            return [];
        }

        $failedData = json_decode(Storage::get($this->failedIdsFile), true) ?? [];

        return array_keys($failedData);
    }
}
