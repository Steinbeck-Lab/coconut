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
    protected $signature = 'coconut:generate-properties-auto {collection_id=65 : The ID of the collection to process} {--force : Retry processing of molecules with failed status only} {--trigger : Trigger subsequent commands in the processing chain}';

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

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        Log::info("Generating properties for molecules in collection ID: {$collection_id}");
        Log::info("Starting property generation for molecules missing properties in collection {$collection_id}...");

        // Base query for molecules filtered by collection
        $query = Molecule::select('molecules.id')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->doesntHave('properties')
            ->distinct();

        // If not forcing, exclude already processed molecules (completed OR failed)
        if (! $forceProcess) {
            $query->where(function ($q) {
                $q->whereNull('curation_status->generate-properties->status')
                    ->orWhereNotIn('curation_status->generate-properties->status', ['completed', 'failed']);
            });
        } else {
            // If forcing, only process molecules with failed status
            $query->where('curation_status->generate-properties->status', 'failed');
        }

        // Get count of molecules to process
        $count = $query->count();
        if ($count === 0) {
            Log::info("No molecules found that require property generation in collection {$collection_id}.");
            if ($triggerNext) {
                Artisan::call('coconut:npclassify-auto', ['collection_id' => $collection_id, '--trigger' => true]);
            }

            return 0;
        }

        Log::info("Found {$count} molecules requiring property generation for collection {$collection_id}");

        // Use chunk to process large sets of molecules
        $query->chunk(30000, function ($mols) use ($collection_id, $triggerNext) {
            $moleculeCount = count($mols);
            Log::info("Processing batch of {$moleculeCount} molecules for property generation in collection {$collection_id}");

            // Mark molecules as processing
            foreach ($mols as $molecule) {
                updateCurationStatus($molecule->id, 'generate-properties', 'processing');
            }

            // Prepare batch jobs
            $batchJobs = [];
            $batchJobs[] = new GeneratePropertiesBatch($mols->pluck('id')->toArray());

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) use ($collection_id, $triggerNext) {
                    Log::info("Properties generation batch completed for collection {$collection_id}: ".$batch->id);
                    if ($triggerNext) {
                        Log::info("Calling NPClassifier batch after GenerateProperties batch for collection {$collection_id}");
                        Artisan::call('coconut:npclassify-auto', ['collection_id' => $collection_id, '--trigger' => true]);
                    }
                })
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("GenerateProperties batch failed for collection {$collection_id}: ".$e->getMessage());

                    // Dispatch event for batch-level notification
                    ImportPipelineJobFailed::dispatch(
                        'Generate Properties Auto Batch',
                        $e,
                        [
                            'batch_id' => $batch->id,
                            'collection_id' => $collection_id,
                            'step' => 'generate_properties_batch',
                        ],
                        $batch->id
                    );
                })
                ->finally(function (Batch $batch) {})
                ->name("Generate Properties Auto Collection {$collection_id}")
                ->allowFailures(true)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        $this->info("Property generation jobs dispatched for collection {$collection_id}!");
    }
}
