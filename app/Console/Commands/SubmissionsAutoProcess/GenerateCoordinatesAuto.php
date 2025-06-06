<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\GenerateCoordinatesBatch;
use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCoordinatesAuto extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:generate-coordinates {collection_id=65 : The ID of the collection to process} {--force : Retry processing of molecules with failed status only}';

    /**
     * The console command description.
     */
    protected $description = 'Generates coordinates (2D/3D) for molecules missing structure data in a specific collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $forceProcess = $this->option('force');

        $collection = Collection::find($collection_id);
        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }
        Log::info("Starting coordinate generation for molecules missing structures in collection ID: {$collection_id}");
        $query = Molecule::select('molecules.id')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('entries.collection_id', $collection_id)
            ->doesntHave('structures')
            ->where('molecules.active', true)
            ->distinct();

        // Flag logic:
        // --force: only when running standalone, picks up failed entries
        if ($forceProcess) {
            // $query->where('curation_status->generate-coordinates->status', 'failed');
        } else {
            // $query->where(function ($q) {
            //     $q->whereNull('curation_status->generate-coordinates->status')
            //         ->orWhereNotIn('curation_status->generate-coordinates->status', ['completed', 'failed']);
            // });
        }

        // Count the total number of molecules to process
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info("No molecules found that require coordinate generation in collection {$collection_id}.");

            return 0;
        }

        Log::info("Total molecules to process in collection {$collection_id}: {$totalCount}");

        // Process molecules in chunks
        $query->chunkById(1000, function ($molecules) use ($collection_id) {
            $moleculeIds = $molecules->pluck('id')->toArray();
            $batchSize = count($moleculeIds);

            Log::info("Processing batch of {$batchSize} molecules for coordinate generation in collection {$collection_id}");

            // Mark molecules as processing
            // foreach ($molecules as $molecule) {
            //     updateCurationStatus($molecule->id, 'generate-coordinates', 'processing');
            // }

            // Create and dispatch batch job
            $batchJobs = [];
            $batchJobs[] = new GenerateCoordinatesBatch($moleculeIds);

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) use ($collection_id) {
                    Log::info("Coordinate generation batch completed successfully for collection {$collection_id}: ".$batch->id);
                })
                ->catch(function (Batch $batch, Throwable $e) use ($collection_id) {
                    Log::error("Coordinate generation batch failed for collection {$collection_id}: ".$e->getMessage());
                })
                ->finally(function (Batch $batch) use ($collection_id) {
                    Log::info("Coordinate generation batch finally block executed for collection {$collection_id}: ".$batch->id);
                })
                ->name("Generate Coordinates Auto Collection {$collection_id}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        Log::info("All coordinate generation jobs dispatched for collection {$collection_id}");

        return 0;
    }
}
