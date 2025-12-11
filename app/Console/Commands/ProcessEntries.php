<?php

namespace App\Console\Commands;

use App\Jobs\LoadEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

class ProcessEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the entries using cheminformatics microservice';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collectionIds = Entry::select('collection_id')->where('status', 'SUBMITTED')->groupBy('collection_id')->get()->toArray();

        foreach ($collectionIds as $collectionId) {
            $batchJobs = [];
            $i = 0;

            $collection = Collection::whereId($collectionId['collection_id'])->first();
            $collection->jobs_status = 'PROCESSING';
            $collection->job_info = 'Processing entries using ChEMBL Pipeline.';
            $collection->save();

            Entry::select('id')->where('status', 'SUBMITTED')->where('collection_id', $collectionId['collection_id'])->chunk(10000, function ($ids) use (&$batchJobs, &$i) {
                array_push($batchJobs, new LoadEntriesBatch($ids->pluck('id')->toArray()));
                $i = $i + 1;
            });
            $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {})->catch(function (Batch $batch, \Throwable $e) {})->finally(function (Batch $batch) use ($collection) {
                $collection->jobs_status = 'INCURATION';
                $collection->job_info = '';
                $collection->save();
                Artisan::call('coconut:entries-import', [
                    'collection_id' => $collection->id,
                ]);
            })->name('Process Entries '.$collectionId['collection_id'])
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        }
    }
}
