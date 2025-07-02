<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ProcessEntryBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEntriesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:validate-molecules {collection_id : The ID of the collection to process}';

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

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        $collection->jobs_status = 'PROCESSING';
        $collection->job_info = 'Auto-processing entries using ChEMBL Pipeline.';
        $collection->save();

        // Count total entries to be processed
        $totalEntriesToProcess = DB::selectOne(
            "SELECT COUNT(*) as count FROM entries WHERE status = 'SUBMITTED' AND molecule_id IS NULL AND collection_id = ?",
            [$collection_id]
        )->count;

        Log::info("Total entries picked up for processing in collection ID {$collection_id}: {$totalEntriesToProcess}");

        $batchJobs = [];

        $batch_count = 0;
        Entry::select('id')
            ->where('status', 'SUBMITTED')
            ->where('molecule_id', null)
            ->where('collection_id', $collection_id)
            ->chunkById(1500, function ($ids) use (&$batchJobs, &$batch_count) {
                $batch_count++;
                $this->info($batch_count.' Processing chunk of '.count($ids).' entries for validation.');
                array_push($batchJobs, new ProcessEntryBatch($ids->pluck('id')->toArray(), $batch_count));
            });

        if (empty($batchJobs)) {
            Log::info("No entries to process for collection ID {$collection_id}.");
            $collection->jobs_status = 'INCURATION';
            $collection->job_info = '';
            $collection->save();

            return 0;
        }

        $batch = Bus::batch($batchJobs)
            ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                Log::error("Batch processing failed for collection ID {$collection_id}: ".$e->getMessage());
            })
            ->finally(function (Batch $batch) use ($collection) {
                $maxWaitTime = 3600; // 1 hour maximum wait
                $startTime = time();
                $pollInterval = 30; // Check every 30 seconds

                // Wait until batch is actually finished
                while (! $batch->finished()) {
                    if ((time() - $startTime) > $maxWaitTime) {
                        Log::error("Batch {$batch->id} timeout, proceeding with cleanup");
                        break;
                    }

                    sleep($pollInterval);

                    // Refresh batch state from database
                    try {
                        $freshBatch = Bus::findBatch($batch->id);
                        if ($freshBatch) {
                            $batch = $freshBatch;
                        }
                    } catch (\Exception $e) {
                        // Continue with stale batch if refresh fails
                    }
                }

                Log::info("Batch processing completed for collection ID {$collection->id}.");
                Log::info('Total jobs: '.$batch->totalJobs.
                    ', Processed jobs: '.$batch->processedJobs().
                    ', Failed jobs: '.$batch->failedJobs);

                if ($batch->finished() && ! $batch->hasFailures()) {
                    $collection->jobs_status = 'INCURATION';
                    $collection->job_info = '';
                    $collection->save();

                    // Trigger next command only on successful completion
                } elseif ($batch->hasFailures()) {
                    $collection->jobs_status = 'COMPLETE';
                    $collection->job_info = "Processing failed: {$batch->failedJobs} jobs failed";
                    $collection->save();

                    // Dispatch PrePublishJobFailed event with batch statistics
                    $batchStats = [
                        'batch_id' => $batch->id,
                        'collection_id' => $collection->id,
                        'collection_name' => $collection->name ?? 'Unknown',
                        'total_jobs' => $batch->totalJobs,
                        'processed_jobs' => $batch->processedJobs(),
                        'failed_jobs' => $batch->failedJobs,
                        'pending_jobs' => $batch->pendingJobs,
                        'progress' => $batch->progress(),
                        'finished_at' => $batch->finishedAt,
                        'cancelled_at' => $batch->cancelledAt,
                    ];

                    $exception = new \Exception("Batch processing failed for collection ID {$collection->id}. {$batch->failedJobs} out of {$batch->totalJobs} jobs failed.");

                    \App\Events\PrePublishJobFailed::dispatch(
                        'Validate Molecules - Batch Failed',
                        $exception,
                        $batchStats,
                        $batch->id
                    );
                }
            })
            ->name('Validate Molecules '.$collection_id)
            ->allowFailures()
            ->onConnection('redis')
            ->onQueue('import')
            ->dispatch();

        Log::info("Processing started for collection ID {$collection_id}.");
    }
}
