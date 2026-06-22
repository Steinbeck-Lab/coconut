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
    protected $signature = 'coconut:npclassify
                            {collection_id? : The ID of the collection to process}
                            {--all : Process all collections}
                            {--force : Re-classify molecules that already have NP Classifier data}';

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
        $force = $this->option('force');

        if (! $collection_id && ! $this->option('all')) {
            Log::error('Please specify either a collection_id or use --all flag');

            return 1;
        }

        if ($collection_id !== null) {
            $collection = Collection::find($collection_id);
            if (! $collection) {
                Log::error("Collection with ID {$collection_id} not found.");

                return 1;
            }
        }

        $collectionLabel = $collection_id !== null ? "collection ID: {$collection_id}" : 'all collections';

        Log::info("Classifying molecules using NPClassifier for {$collectionLabel}".($force ? ' (force re-classify)' : ''));

        if ($force) {
            $conditions = '
            WHERE molecules.active = true
              AND (
                properties.np_classifier_pathway IS NOT NULL
                OR properties.np_classifier_superclass IS NOT NULL
                OR properties.np_classifier_class IS NOT NULL
              )
        ';
        } else {
            $conditions = '
            WHERE molecules.active = true
              AND properties.np_classifier_pathway IS NULL
              AND properties.np_classifier_superclass IS NULL
              AND properties.np_classifier_class IS NULL
              AND properties.np_classifier_is_glycoside IS NULL
        ';
        }

        $bindings = [];
        if ($collection_id !== null) {
            $classifiedClause = $force
                ? '(
                properties.np_classifier_pathway IS NOT NULL
                OR properties.np_classifier_superclass IS NOT NULL
                OR properties.np_classifier_class IS NOT NULL
              )'
                : 'properties.np_classifier_pathway IS NULL
              AND properties.np_classifier_superclass IS NULL
              AND properties.np_classifier_class IS NULL
              AND properties.np_classifier_is_glycoside IS NULL';

            $conditions = "
            WHERE entries.collection_id = ?
              AND molecules.active = true
              AND {$classifiedClause}
            ";
            $bindings[] = $collection_id;
        }

        $sql = '
            SELECT DISTINCT molecules.id, molecules.canonical_smiles
            FROM molecules
            INNER JOIN entries ON entries.molecule_id = molecules.id
            INNER JOIN properties ON properties.molecule_id = molecules.id
        '.$conditions.'
            ORDER BY molecules.id
        ';

        $molecules = DB::select($sql, $bindings);

        $totalCount = count($molecules);
        if ($totalCount === 0) {
            Log::info("No molecules found to classify in {$collectionLabel}.");

            return 0;
        }

        Log::info("Starting NPClassifier for {$totalCount} molecules in {$collectionLabel}");

        // Chunk the results manually
        $chunks = array_chunk($molecules, 1000);

        foreach ($chunks as $chunk) {
            $moleculeIds = array_map(fn ($row) => $row->id, $chunk);
            $moleculeCount = count($moleculeIds);

            Log::info("Processing batch of {$moleculeCount} molecules for classification in {$collectionLabel}");

            $batchJobs = [];
            $batchJobs[] = new ClassifyMoleculeBatch($moleculeIds, $force);

            Bus::batch($batchJobs)
                ->catch(function (Batch $batch, Throwable $e) use ($collectionLabel) {
                    Log::error("NPClassifier batch failed for {$collectionLabel}: ".$e->getMessage());
                })
                ->name('NPClassifier Batch Auto '.ucfirst($collectionLabel))
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        }

        Log::info("All classification jobs have been dispatched for {$collectionLabel}!");

        return 0;
    }
}
