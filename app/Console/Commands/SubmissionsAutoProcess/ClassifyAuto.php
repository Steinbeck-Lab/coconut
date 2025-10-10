<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ClassifyMoleculeBatch;
use App\Models\Collection;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
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

        // Use raw query to avoid ambiguous column issues
        $sql = '
            SELECT DISTINCT molecules.id, molecules.canonical_smiles
            FROM molecules
            INNER JOIN entries ON entries.molecule_id = molecules.id
            INNER JOIN properties ON properties.molecule_id = molecules.id
            WHERE entries.collection_id = ?
              AND molecules.active = true
              AND properties.np_classifier_pathway IS NULL
              AND properties.np_classifier_superclass IS NULL
              AND properties.np_classifier_class IS NULL
              AND properties.np_classifier_is_glycoside IS NULL
            ORDER BY molecules.id
        ';

        $molecules = DB::select($sql, [$collection_id]);

        $totalCount = count($molecules);
        if ($totalCount === 0) {
            Log::info("No molecules found to classify in collection {$collection_id}.");

            return 0;
        }

        Log::info("Starting NPClassifier for {$totalCount} molecules in collection {$collection_id}");

        // Chunk the results manually
        $chunks = array_chunk($molecules, 1000);

        foreach ($chunks as $chunk) {
            $moleculeIds = array_map(fn ($row) => $row->id, $chunk);
            $moleculeCount = count($moleculeIds);

            Log::info("Processing batch of {$moleculeCount} molecules for classification in collection {$collection_id}");

            $batchJobs = [];
            $batchJobs[] = new ClassifyMoleculeBatch($moleculeIds);

            Bus::batch($batchJobs)
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("NPClassifier batch failed for collection {$collection_id}: ".$e->getMessage());
                })
                ->name("NPClassifier Batch Auto Collection {$collection_id}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        }

        Log::info("All classification jobs have been dispatched for collection {$collection_id}!");

        return 0;
    }
}
