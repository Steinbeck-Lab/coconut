<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\GeneratePropertiesBatch;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class GeneratePropertiesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-properties-auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates properties for molecules for all molecules where properties are missing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Use chunk to process large sets of molecules
        Molecule::doesntHave('properties')->select('id')
            ->chunk(30000, function ($mols) {
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
                    ->name('Generate Properties Auto')
                    ->allowFailures(false)
                    ->onConnection('redis')
                    ->onQueue('default')
                    ->dispatch();
            });
    }
}
