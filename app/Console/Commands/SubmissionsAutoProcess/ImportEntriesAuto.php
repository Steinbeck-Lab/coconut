<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportEntriesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-import-auto {collection_id=65 : The ID of the collection to import} {--trigger : Trigger subsequent commands in the processing chain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-import CheMBL processed entries for a specific collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $triggerNext = $this->option('trigger');

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        Log::info("Importing entries for collection ID: {$collection_id}");

        // Update collection status
        $collection->jobs_status = 'PROCESSING';
        $collection->job_info = 'Auto-importing entries: Citations, Organism Info and other details';
        $collection->save();

        $batchJobs = [];

        Entry::select('id')
            ->where('status', 'PASSED')
            ->where('molecule_id', null)
            ->where('collection_id', $collection_id)
            ->chunk(10000, function ($ids) use (&$batchJobs) {
                $this->info('Found '.count($ids).' entries to import');
                array_push($batchJobs, new ImportEntriesBatch($ids->pluck('id')->toArray(), 'auto'));
            });

        if (empty($batchJobs)) {
            Log::info("No entries to import for collection ID {$collection_id}.");
            $collection->jobs_status = 'INCURATION';
            $collection->job_info = '';
            $collection->save();

            if ($triggerNext) {
                Artisan::call('coconut:molecules-assign-identifiers-auto', [
                    'collection_id' => $collection_id,
                    '--trigger' => true,
                ]);
            }

            return 0;
        }

        Log::info('Dispatching import batch...');

        $batch = Bus::batch($batchJobs)
            ->then(function (Batch $batch) use ($collection_id, $triggerNext) {
                Log::info("Entries imported for collection ID {$collection_id}.");
                // Clear cache for this collection only
                Cache::forget('stats.collections'.$collection_id.'entries.count');
                Cache::forget('stats.collections'.$collection_id.'molecules.count');

                // Call the next command in the chain with the same collection ID
                if ($triggerNext) {
                    Artisan::call('coconut:molecules-assign-identifiers-auto', [
                        'collection_id' => $collection_id,
                        '--trigger' => true,
                    ]);
                }
            })
            ->catch(function (Batch $batch, Throwable $e) {})
            ->finally(function (Batch $batch) use ($collection) {
                if ($batch->finished() && ! $batch->hasFailures()) {
                    $collection->jobs_status = 'INCURATION';
                    $collection->job_info = '';
                    $collection->save();
                }
            })
            ->name('Import Entries Auto '.$collection_id)
            ->allowFailures(false)
            ->onConnection('redis')
            ->onQueue('default')
            ->dispatch();
    }
}
