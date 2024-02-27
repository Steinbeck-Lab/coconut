<?php

namespace App\Console\Commands;

use App\Jobs\ImportEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ImportEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'entries:import';

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
        $collections = Collection::where('status', 'PUBLISHED')->get();
        foreach ($collections as $collection) {
            $batchJobs = [];
            $i = 0;
            Entry::select('id')->where('status', 'PASSED')->where('collection_id', $collection->id)->chunk(100, function ($ids) use (&$batchJobs, &$i) {
                array_push($batchJobs, new ImportEntriesBatch($ids->pluck('id')->toArray()));
                $i = $i + 1;
            });
            $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {
                // All jobs completed successfully...
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })->finally(function (Batch $batch) {
                // The batch has finished executing...
            })->name('Import Entries '.$collection->id)
                ->allowFailures(false)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        }
    }
}
