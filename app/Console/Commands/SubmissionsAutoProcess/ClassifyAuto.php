<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ClassifyMoleculeBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyAuto extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:npclassify {collection_id=65 : The ID of the collection to process} {--force : Retry processing of molecules with failed status only} {--trigger : Trigger subsequent commands in the processing chain} {--trigger-force : Trigger downstream commands and pick up failed rows}';

    /**
     * The console command description.
     */
    protected $description = 'Classifies molecules using NPClassifier for molecules in a specific collection';

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
        Log::info("Classifying molecules using NPClassifier for collection ID: {$collection_id}");
        $query = Molecule::select('molecules.id', 'molecules.canonical_smiles')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->where('molecules.active', true)
            ->whereHas('properties', function ($q) {
                $q->whereNull('np_classifier_pathway')
                    ->whereNull('np_classifier_superclass')
                    ->whereNull('np_classifier_class')
                    ->whereNull('np_classifier_is_glycoside');
            })
            ->distinct();

        // Flag logic:
        // --force: only when running standalone, picks up failed entries
        // --trigger: triggers downstream, does NOT pick up failed entries
        // --trigger-force: triggers downstream AND picks up failed entries
        if ($triggerForce || $forceProcess) {
            // $query->where('curation_status->classify->status', 'failed');
        } else {
            // $query->where(function ($q) {
            //     $q->whereNull('curation_status->classify->status')
            //         ->orWhereNotIn('curation_status->classify->status', ['completed', 'failed']);
            // });
        }

        Log::info("Processing molecules in collection {$collection_id} that need classification.");

        // Count the total number of molecules to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No molecules found to classify in collection {$collection_id}.");
            if ($triggerForce) {
                Artisan::call('coconut:generate-coordinates', [
                    'collection_id' => $collection_id,
                    '--force' => true,
                ]);
            } elseif ($triggerNext) {
                Artisan::call('coconut:generate-coordinates', [
                    'collection_id' => $collection_id,
                ]);
            }

            return 0;
        }

        Log::info("Total molecules to process in collection {$collection_id}: {$totalCount}");
        Log::info("Starting NPClassifier for {$totalCount} molecules in collection {$collection_id}");

        // Process molecules in chunks and create batch jobs
        $query->chunkById(1000, function ($molecules) use ($collection_id, $triggerNext, $triggerForce) {
            $moleculeCount = count($molecules);
            Log::info("Processing batch of {$moleculeCount} molecules for classification in collection {$collection_id}");

            // Mark molecules as processing
            // foreach ($molecules as $molecule) {
            //     updateCurationStatus($molecule->id, 'classify', 'processing');
            // }

            $batchJobs = [];
            $moleculeIds = $molecules->pluck('id')->toArray();
            $batchJobs[] = new ClassifyMoleculeBatch($moleculeIds);

            // Dispatch the batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) {})
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("NPClassifier batch failed for collection {$collection_id}: ".$e->getMessage());
                })
                ->finally(function (Batch $batch) use ($collection_id, $triggerNext, $triggerForce) {
                    if ($triggerForce) {
                        Artisan::call('coconut:generate-coordinates', [
                            'collection_id' => $collection_id,
                            '--force' => true,
                        ]);
                    } elseif ($triggerNext) {
                        Artisan::call('coconut:generate-coordinates', [
                            'collection_id' => $collection_id,
                        ]);
                    }
                })
                ->name("NPClassifier Batch Auto Collection {$collection_id}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        Log::info("All classification jobs have been dispatched for collection {$collection_id}!");

        return 0;
    }
}
