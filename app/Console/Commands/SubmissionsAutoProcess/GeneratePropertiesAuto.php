<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

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
    protected $signature = 'coconut:generate-properties-auto {collection_id=65 : The ID of the collection to process}';

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

        Log::info("Generating properties for molecules in collection ID: {$collection_id}");
        Log::info("Starting property generation for molecules missing properties in collection {$collection_id}...");

        // Use chunk to process large sets of molecules filtered by collection
        Molecule::select('molecules.id')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->doesntHave('properties')
            ->distinct()
            ->chunk(30000, function ($mols) use ($collection_id) {
                $moleculeCount = count($mols);
                Log::info("Processing batch of {$moleculeCount} molecules for property generation in collection {$collection_id}");

                // Prepare batch jobs
                $batchJobs = [];
                $batchJobs[] = new GeneratePropertiesBatch($mols->pluck('id')->toArray());

                // Dispatch as a batch
                Bus::batch($batchJobs)
                    ->then(function (Batch $batch) use ($collection_id) {
                        Log::info("Calling NPClassifier batch after GenerateProperties batch for collection {$collection_id}");
                        Artisan::call('coconut:npclassify-auto', ['collection_id' => $collection_id]);
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                        Log::error("GenerateProperties batch failed for collection {$collection_id}: ".$e->getMessage());
                    })
                    ->finally(function (Batch $batch) {
                        // Handle final...
                    })
                    ->name("Generate Properties Auto Collection {$collection_id}")
                    ->allowFailures(true)
                    ->onConnection('redis')
                    ->onQueue('default')
                    ->dispatch();
            });

        $this->info("Property generation jobs dispatched for collection {$collection_id}!");
    }
}
