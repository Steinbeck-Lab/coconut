<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\GeneratePropertiesBatch;
use App\Models\Collection;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
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

        // Use raw query to avoid ambiguous column issues
        $sql = '
        SELECT molecules.id
        FROM molecules
        INNER JOIN collection_molecule ON collection_molecule.molecule_id = molecules.id
        LEFT JOIN properties ON properties.molecule_id = molecules.id
        WHERE collection_molecule.collection_id = ?
          AND molecules.active = true
          AND properties.molecule_id IS NULL
        ORDER BY molecules.id
    ';

        $moleculeIds = DB::select($sql, [$collection_id]);

        $totalCount = count($moleculeIds);
        if ($totalCount === 0) {
            Log::info("No molecules found that require property generation in collection {$collection_id}.");

            return 0;
        }

        Log::info("Starting property generation for {$totalCount} molecules in collection {$collection_id}.");

        // Chunk the results manually
        $chunks = array_chunk($moleculeIds, 1000);

        foreach ($chunks as $chunk) {
            $ids = array_map(fn ($row) => $row->id, $chunk);
            $moleculeCount = count($ids);

            Log::info("Processing batch of {$moleculeCount} molecules for property generation in collection {$collection_id}");

            $batchJobs = [];
            $batchJobs[] = new GeneratePropertiesBatch($ids);

            Bus::batch($batchJobs)
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("Batch job failed for collection {$collection_id}: ".$e->getMessage());
                })
                ->name("Generate Properties Auto Collection {$collection_id}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        }

        $this->info("Property generation jobs dispatched for collection {$collection_id}!");
    }
}
