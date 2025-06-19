<?php

namespace App\Console\Commands;

use App\Jobs\ImportEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ImportEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-import {collection_id?}';

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
        $collection_id = $this->argument('collection_id');

        if (! is_null($collection_id)) {
            $collections = Collection::where('id', $collection_id)->get();
        } else {
            $collections = Collection::where('status', 'DRAFT')->get();
        }

        foreach ($collections as $collection) {
            $collection->jobs_status = 'PROCESSING';
            $collection->job_info = 'Importing entries: Citations, Organism Info and other details';
            $collection->save();

            $batchJobs = [];
            $i = 0;
            Entry::select('id')->where('status', 'PASSED')->where('collection_id', $collection->id)->chunk(1000, function ($ids) use (&$batchJobs, &$i) {
                array_push($batchJobs, new ImportEntriesBatch($ids->pluck('id')->toArray(), null));
                $i = $i + 1;
            });
            $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {})->catch(function (Batch $batch, Throwable $e) {})->finally(function (Batch $batch) use ($collection) {
                $collection->jobs_status = 'INCURATION';
                $collection->job_info = '';
                $collection->save();
                Cache::forget('stats.collections'.$collection->id.'molecules.count');
            })->name('Import Entries '.$collection->id)
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        }
    }
}
