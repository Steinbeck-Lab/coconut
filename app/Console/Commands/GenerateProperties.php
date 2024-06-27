<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePropertiesBatch;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class GenerateProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'molecules:generate-properties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $i = 0;
        Molecule::doesntHave('properties')->select('id')->chunk(30000, function ($mols) use (&$i) {
            $batchJobs = [];
            array_push($batchJobs, new GeneratePropertiesBatch($mols->pluck('id')->toArray()));
            $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {
            })->catch(function (Batch $batch, Throwable $e) {
            })->finally(function (Batch $batch) {
            })->name('Generate Properties Batch:'.$i)
                ->allowFailures(false)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
            $i = $i + 1;
        });
    }
}
