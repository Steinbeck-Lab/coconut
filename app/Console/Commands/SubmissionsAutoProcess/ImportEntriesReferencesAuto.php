<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Events\ImportPipelineJobFailed;
use App\Jobs\ImportEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
    protected $signature = 'coconut:entries-import-references {collection_id=65 : The ID of the collection to import references for} {--force : Retry processing of molecules with failed status only} {--trigger : Trigger subsequent commands in the processing chain} {--trigger-force : Trigger downstream commands and pick up failed rows}';

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
        $force = $this->option('force');
        $triggerNext = $this->option('trigger');
        $triggerForce = $this->option('trigger-force');

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

        $query = Entry::select('id')
            ->where('status', 'AUTOCURATION')
            ->where('molecule_id', '!=', null)
            ->where('collection_id', $collection_id)
            ->whereHas('molecule', function ($q) {
                $q->where('active', true);
            });

        // Flag logic:
        // --force: only when running standalone, picks up failed entries
        // --trigger: triggers downstream, does NOT pick up failed entries
        // --trigger-force: triggers downstream AND picks up failed entries
        if ($triggerForce || $force) {
            // Pick up failed entries if either flag is set
            $query->whereHas('molecule', function ($q) {
                $q->where('curation_status->import-entries-references->status', 'failed');
            });
        } else {
            // Default: only pick up entries not completed/failed
            $query->whereHas('molecule', function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('curation_status->import-entries-references->status')
                        ->orWhereNotIn('curation_status->import-entries-references->status', ['completed', 'failed']);
                });
            });
        }

        // Count the total number of entries to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No entries found in AUTOCURATION status for collection ID {$collection_id}.");
            $collection->jobs_status = 'COMPLETE';
            $collection->job_info = '';
            $collection->save();

            // Trigger next command if specified
            if ($triggerForce) {
                Artisan::call('coconut:import-pubchem-data-auto', [
                    'collection_id' => $collection_id,
                    '--trigger-force' => true,
                ]);
            } elseif ($triggerNext) {
                Artisan::call('coconut:import-pubchem-data-auto', [
                    'collection_id' => $collection_id,
                    '--trigger' => true,
                ]);
            }

            return 0;
        }

        $query->chunk(10000, function ($ids) use (&$batchJobs) {
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

        $batch = Bus::batch($batchJobs)->then(function (Batch $batch) use ($collection_id, $triggerNext, $triggerForce) {
            // Call the next command in the chain with the same collection ID
            if ($triggerForce) {
                Artisan::call('coconut:import-pubchem-data-auto', [
                    'collection_id' => $collection_id,
                    '--trigger-force' => true,
                ]);
            } elseif ($triggerNext) {
                Artisan::call('coconut:import-pubchem-data-auto', [
                    'collection_id' => $collection_id,
                    '--trigger' => true,
                ]);
            }
        })
            ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                Log::error('References import batch failed: '.$e->getMessage());

                // Dispatch event for batch-level notification
                ImportPipelineJobFailed::dispatch(
                    'Import References Auto Batch',
                    $e,
                    [
                        'batch_id' => $batch->id,
                        'collection_id' => $collection_id,
                        'step' => 'import_references_batch',
                    ],
                    $batch->id
                );
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
