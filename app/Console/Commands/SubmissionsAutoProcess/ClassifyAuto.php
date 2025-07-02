<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ClassifyMoleculeBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyAuto extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:npclassify {collection_id : The ID of the collection to process}';

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

        Log::info("Processing molecules in collection {$collection_id} that need classification.");

        // Count the total number of molecules to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No molecules found to classify in collection {$collection_id}.");

            return 0;
        }

        Log::info("Starting NPClassifier for {$totalCount} molecules in collection {$collection_id}");

        // Process molecules in chunks and create batch jobs
        $query->chunkById(1000, function ($molecules) use ($collection_id) {
            $moleculeCount = count($molecules);
            Log::info("Processing batch of {$moleculeCount} molecules for classification in collection {$collection_id}");

            $batchJobs = [];
            $moleculeIds = $molecules->pluck('id')->toArray();
            $batchJobs[] = new ClassifyMoleculeBatch($moleculeIds);

            // Dispatch the batch
            Bus::batch($batchJobs)
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("NPClassifier batch failed for collection {$collection_id}: ".$e->getMessage());
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
