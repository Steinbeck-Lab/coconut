<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportPubChemBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportPubChemNamesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-pubchem-data {collection_id : The ID of the collection to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import PubChem data for molecules without names for a specific collection, excluding previously failed ones unless retry is specified';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $retryFailed = $this->option('retry-failed');

        $collection = Collection::find($collection_id);
        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }
        $query = Molecule::select('molecules.id')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->where(function ($query) {
                $query->whereNull('molecules.name')
                    ->orWhere('molecules.name', '=', '');
            })
            ->whereNull('molecules.iupac_name')
            ->whereNull('molecules.synonyms')
            ->where('molecules.active', true)
            ->distinct();

        // Flag logic:
        // --force: only when running standalone, picks up failed entries
        // --trigger: triggers downstream, does NOT pick up failed entries
        // --trigger-force: triggers downstream AND picks up failed entries
        if ($retryFailed) {
            $query->where('curation_status->import-pubchem-names->status', 'failed');
        } else {
            $query->where(function ($q) {
                $q->whereNull('curation_status->import-pubchem-names->status')
                    ->orWhereNotIn('curation_status->import-pubchem-names->status', ['failed']);
            });
        }

        // Count the total number of molecules to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No molecules found that require PubChem data import for collection {$collection_id}.");

            return 0;
        }

        Log::info("Starting PubChem data import for {$totalCount} molecules in collection {$collection_id}.");

        // Use chunk to process large sets of molecules
        $query->chunkById(10000, function ($mols) use ($collection_id) {
            $moleculeCount = count($mols);
            Log::info("Processing batch of {$moleculeCount} molecules for collection {$collection_id}");

            // Prepare batch jobs
            $batchJobs = [];
            $batchJobs[] = new ImportPubChemBatch($mols->pluck('id')->toArray());

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("PubChem import batch failed for collection {$collection_id}: ".$e->getMessage());
                })
                ->name("Import PubChem Auto Batch Collection {$collection_id}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        Log::info("All PubChem import jobs dispatched for collection {$collection_id}!");
    }
}
