<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\LoadEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEntriesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-process-auto {collection_id=65 : The ID of the collection to process} {--trigger : Trigger subsequent commands in the processing chain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-process the entries using cheminformatics microservice for a specific collection';

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

        $collection->jobs_status = 'PROCESSING';
        $collection->job_info = 'Auto-processing entries using ChEMBL Pipeline.';
        $collection->save();

        $batchJobs = [];

        Entry::select('id')
            ->where('status', 'SUBMITTED')
            ->where('molecule_id', null)
            ->where('collection_id', $collection_id)
            ->chunk(10000, function ($ids) use (&$batchJobs) {
                array_push($batchJobs, new LoadEntriesBatch($ids->pluck('id')->toArray()));
            });

        if (empty($batchJobs)) {
            Log::info("No entries to process for collection ID {$collection_id}.");
            $collection->jobs_status = 'INCURATION';
            $collection->job_info = '';
            $collection->save();

            return 0;
        }

        $batch = Bus::batch($batchJobs)
            ->then(function (Batch $batch) use ($collection_id, $triggerNext) {
                if ($triggerNext) {
                    Artisan::call('coconut:entries-import-auto', [
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
            ->name('Process Entries Auto '.$collection_id)
            ->allowFailures(false)
            ->onConnection('redis')
            ->onQueue('default')
            ->dispatch();

        Log::info("Processing started for collection ID {$collection_id}.");
    }
}
