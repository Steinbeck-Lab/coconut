<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\GenerateCoordinatesBatch;
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
    protected $signature = 'coconut:generate-coordinates-auto';

    /**
     * The console command description.
     */
    protected $description = 'Generates coordinates (2D/3D) for molecules missing structure data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting coordinate generation for molecules missing structures...');

        // Retrieve all molecules missing structures
        $query = Molecule::doesntHave('structures')->select('id');
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No molecules found that require coordinate generation.');

            return 0;
        }

        // Process molecules in chunks of 1000
        $query->chunkById(1000, function ($molecules) {
            $moleculeIds = $molecules->pluck('id')->toArray();
            $batchSize = count($moleculeIds);

            // Create and dispatch batch job
            $batchJobs = [];
            $batchJobs[] = new GenerateCoordinatesBatch($moleculeIds);

            // Dispatch as a batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) {
                    Log::info('Coordinate generation batch completed successfully: '.$batch->id);
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    Log::error('Coordinate generation batch failed: '.$e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    // Cleanup or final logging
                })
                ->name('Generate Coordinates Auto')
                ->allowFailures(true)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });

        $this->info('All coordinate generation jobs have been dispatched successfully!');

        return 0;
    }
}
