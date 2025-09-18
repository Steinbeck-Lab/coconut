<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\GeneratePropertiesBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
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
    protected $signature = 'coconut:generate-properties {collection_id : The ID of the collection to process}';

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

        $collection = Collection::find($collection_id);
        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        $query = Molecule::select('molecules.id')
            ->join('collection_molecule', 'collection_molecule.molecule_id', '=', 'molecules.id')
            ->leftJoin('properties', 'properties.molecule_id', '=', 'molecules.id')
            ->where('collection_molecule.collection_id', $collection_id)
            ->where('molecules.active', true)
            ->whereNull('properties.molecule_id')  // More efficient than doesntHave
            ->orderBy('molecules.id');

        // Flag logic:
        // --force: only when running standalone, picks up failed entries
        // --trigger: triggers downstream, does NOT pick up failed entries
        // --trigger-force: triggers downstream AND picks up failed entries

        // Count the total number of molecules to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No molecules found that require property generation in collection {$collection_id}.");

            return 0;
        }

        Log::info("Starting property generation for {$totalCount} molecules in collection {$collection_id}.");

        // Use chunk to process large sets of molecules
        $query->chunkById(1000, function ($molecules) use ($collection_id) {
            $moleculeCount = count($molecules);
            Log::info("Processing batch of {$moleculeCount} molecules for property generation in collection {$collection_id}");

            // Prepare batch jobs
            $batchJobs = [];
            $batchJobs[] = new GeneratePropertiesBatch($molecules->pluck('id')->toArray());

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("Batch job failed for collection {$collection_id}: ".$e->getMessage());
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
