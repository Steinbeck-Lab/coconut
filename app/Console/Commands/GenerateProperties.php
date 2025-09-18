<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePropertiesBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class GenerateProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-properties-old {collection_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates properties for molecules either for a specific collection or for all molecules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Grab the 'collection_id' argument, which may be null or an integer
        $collection_id = $this->argument('collection_id');

        // Decide which query to build based on whether collection_id is null
        if (! is_null($collection_id)) {
            // Attempt to find the specified collection
            $collection = Collection::find($collection_id);

            if (! $collection) {
                // If the collection doesn’t exist, you can either exit or handle differently
                $this->error("Collection with ID {$collection_id} not found.");

                return;
            }

            // Retrieve only molecules from this collection that do not have properties
            $query = $collection->molecules()->doesntHave('properties');
        } else {
            // No collection_id provided, so handle all molecules
            // Add any extra filters you need here:
            // ->where('some_field', 'some_value')
            $query = Molecule::doesntHave('properties');
        }

        $i = 0;

        // Use chunk to process large sets of molecules
        $query->select('molecules.id')
            ->chunk(30000, function ($mols) use (&$i, &$collection_id) {
                // Prepare batch jobs
                $batchJobs = [];
                $batchJobs[] = new GeneratePropertiesBatch($mols->pluck('id')->toArray());

                // Dispatch as a batch
                Bus::batch($batchJobs)
                    ->then(function (Batch $batch) {
                        // Handle success...
                    })
                    ->catch(function (Batch $batch, Throwable $e) {
                        // Handle failure...
                    })
                    ->finally(function (Batch $batch) {
                        // Handle final...
                    })
                    ->name('Generate Properties:'.$collection_id.' - '.$i)
                    ->allowFailures()
                    ->onConnection('redis')
                    ->onQueue('default')
                    ->dispatch();

                $i++;
            });
    }
}
