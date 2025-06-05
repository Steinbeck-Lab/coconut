<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Events\ImportPipelineJobFailed;
use App\Jobs\GeneratePropertiesBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePropertiesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-properties-auto {collection_id=65 : The ID of the collection to process} {--force : Retry processing of molecules with failed status only} {--trigger : Trigger subsequent commands in the processing chain} {--trigger-force : Trigger downstream commands and pick up failed rows}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates properties for molecules in a specific collection where properties are missing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $forceProcess = $this->option('force');
        $triggerNext = $this->option('trigger');
        $triggerForce = $this->option('trigger-force');

        $collection = Collection::find($collection_id);
        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }
        Log::info("Starting property generation for molecules in collection ID: {$collection_id}");
        $query = Molecule::select('molecules.id')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->where('molecules.active', true)
            ->doesntHave('properties')
            ->distinct();

        // Flag logic:
        // --force: only when running standalone, picks up failed entries
        // --trigger: triggers downstream, does NOT pick up failed entries
        // --trigger-force: triggers downstream AND picks up failed entries
        if ($triggerForce || $forceProcess) {
            $query->where('curation_status->generate-properties->status', 'failed');
        } else {
            $query->where(function ($q) {
                $q->whereNull('curation_status->generate-properties->status')
                    ->orWhereNotIn('curation_status->generate-properties->status', ['completed', 'failed']);
            });
        }

        // Count the total number of molecules to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No molecules found that require property generation in collection {$collection_id}.");
            // Trigger next command if specified
            if ($triggerForce) {
                Artisan::call('coconut:npclassify-auto', [
                    'collection_id' => $collection_id,
                    '--trigger-force' => true,
                ]);
            } elseif ($triggerNext) {
                Artisan::call('coconut:npclassify-auto', [
                    'collection_id' => $collection_id,
                    '--trigger' => true,
                ]);
            }

            return 0;
        }

        Log::info("Found {$totalCount} molecules requiring property generation for collection {$collection_id}");

        // Use chunk to process large sets of molecules
        $query->chunkById(1000, function ($molecules) use ($collection_id, $triggerNext, $triggerForce) {
            $moleculeCount = count($molecules);
            Log::info("Processing batch of {$moleculeCount} molecules for property generation in collection {$collection_id}");

            // Mark molecules as processing
            // foreach ($molecules as $molecule) {
            //     updateCurationStatus($molecule->id, 'generate-properties', 'processing');
            // }

            // Prepare batch jobs
            $batchJobs = [];
            $batchJobs[] = new GeneratePropertiesBatch($molecules->pluck('id')->toArray());

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) {})
                ->catch(function (Batch $batch, Throwable $e) {
                    // Log::error("GenerateProperties batch failed for collection {$collection_id}: ".$e->getMessage());

                    // // Dispatch event for batch-level notification
                    // ImportPipelineJobFailed::dispatch(
                    //     'Generate Properties Auto Batch',
                    //     $e,
                    //     [
                    //         'batch_id' => $batch->id,
                    //         'collection_id' => $collection_id,
                    //         'step' => 'generate_properties_batch',
                    //     ],
                    //     $batch->id
                    // );
                })
                ->finally(function (Batch $batch) use ($collection_id, $triggerNext, $triggerForce) {
                    Log::info("Property generation batch completed for collection {$collection_id}: ".$batch->id);
                    if ($triggerForce) {
                        Artisan::call('coconut:npclassify-auto', [
                            'collection_id' => $collection_id,
                            '--trigger-force' => true,
                        ]);
                    } elseif ($triggerNext) {
                        Artisan::call('coconut:npclassify-auto', [
                            'collection_id' => $collection_id,
                            '--trigger' => true,
                        ]);
                    }
                })
                ->name("Generate Properties Auto Collection {$collection_id}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        $this->info("Property generation jobs dispatched for collection {$collection_id}!");
    }
}
