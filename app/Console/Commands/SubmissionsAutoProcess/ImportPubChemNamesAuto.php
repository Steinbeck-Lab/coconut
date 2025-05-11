<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportPubChemBatch;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Log;
use Throwable;

class ImportPubChemNamesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-pubchem-data-auto {--retry-failed : Include previously failed molecules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import PubChem data for molecules without names, excluding previously failed ones unless retry is specified';

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
        $this->info('Importing PubChem data...');

        // Load the list of failed molecule IDs
        $failedIds = $this->getFailedMoleculeIds();
        $retryFailed = $this->option('retry-failed');

        // Base query to find molecules needing PubChem data
        $query = Molecule::where(function ($query) {
            $query->whereNull('name')
                ->orWhere('name', '=', '');
        })
            ->whereNull('iupac_name')
            ->whereNull('synonyms');
        // $query = Molecule::where('id', 1080882);

        // Exclude failed molecules unless retry is specified
        if (! $retryFailed && count($failedIds) > 0) {
            Log::info('Excluding '.count($failedIds).' previously failed molecules. Use --retry-failed to include them.');
            $query->whereNotIn('id', $failedIds);
        }

        // Use chunk to process large sets of molecules
        $query->select('id')
            ->chunkById(10000, function ($mols) {
                $moleculeCount = count($mols);
                $this->info("Processing batch of {$moleculeCount} molecules");

                // Prepare batch jobs
                $batchJobs = [];
                $batchJobs[] = new ImportPubChemBatch($mols->pluck('id')->toArray());

                // Dispatch as a batch
                Bus::batch($batchJobs)
                    ->then(function (Batch $batch) {
                        // Artisan::call('coconut:generate-properties-auto');
                    })
                    ->catch(function (Batch $batch, Throwable $e) {
                        Log::error('PubChem import batch failed: '.$e->getMessage());
                    })
                    ->finally(function (Batch $batch) {
                        // Cleanup or logging can happen here
                    })
                    ->name('Import PubChem Auto Batch')
                    ->allowFailures(false)
                    ->onConnection('redis')
                    ->onQueue('default')
                    ->dispatch();
            });

        $this->info('All PubChem import jobs dispatched!');
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
