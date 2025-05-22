<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Artisan;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportEntriesReferencesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-import-references {collection_id=65 : The ID of the collection to import references for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import references and organism details for entries in AUTOCURATION status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        Log::info("Importing references for collection ID: {$collection_id}");

        // Update collection status
        $collection->jobs_status = 'PROCESSING';
        $collection->job_info = 'Importing references: Citations and Organism Info';
        $collection->save();

        $batchJobs = [];

        Entry::select('id')
            ->where('status', 'AUTOCURATION')
            ->where('molecule_id', '!=', null)
            ->where('collection_id', $collection_id)
            ->chunk(10000, function ($ids) use (&$batchJobs) {
                $this->info('Found '.count($ids).' entries to process references for');
                array_push($batchJobs, new ImportEntriesBatch($ids->pluck('id')->toArray(), 'references'));
            });

        if (empty($batchJobs)) {
            Log::info("No entries in AUTOCURATION status found for collection ID {$collection_id}.");
            $collection->jobs_status = 'COMPLETE';
            $collection->job_info = '';
            $collection->save();

            return 0;
        }

        Log::info('Dispatching references import batch...');

        $batch = Bus::batch($batchJobs)
            ->then(function (Batch $batch) use ($collection_id) {
                // Call the next command in the chain with the same collection ID
                Artisan::call('coconut:import-pubchem-data-auto', [
                    'collection_id' => $collection_id,
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error('References import batch failed: '.$e->getMessage());
            })
            ->finally(function (Batch $batch) use ($collection) {
                if ($batch->finished() && ! $batch->hasFailures()) {
                    $collection->jobs_status = 'INCURATION';
                    $collection->job_info = '';
                    $collection->save();
                }
            })
            ->name('Import References Auto '.$collection_id)
            ->allowFailures(false)
            ->onConnection('redis')
            ->onQueue('default')
            ->dispatch();

        Log::info("References import process started for collection ID {$collection_id}.");
    }
}
