<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Molecule;
use DB;
use Illuminate\Support\Facades\Http;
use App\Jobs\ImportPubChemBatch;
use Illuminate\Support\Facades\Bus;
use App\Jobs\GeneratePropertiesBatch;

class ImportPubChem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'molecules:import-pubchem';

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
        Molecule::whereNull('iupac_name')->where('id' , '<', 5000)->select('id')->chunk(1000000, function ($mols) use (&$i) {
            $batchJobs = [];
            array_push($batchJobs, new ImportPubChemBatch($mols->pluck('id')->toArray()));
            $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {
            })->catch(function (Batch $batch, Throwable $e) {
            })->finally(function (Batch $batch) {
            })->name('PubChem Import Batch:'. $i)
                ->allowFailures(false)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
            $i = $i + 1;
        });
    }
}
