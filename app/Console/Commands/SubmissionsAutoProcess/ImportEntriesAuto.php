<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportEntriesBatch;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class ImportEntriesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-import-auto';

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

        $this->info('Importing entries...');
        $collection_ids = Entry::select('collection_id')->where('status', 'PASSED')->where('molecule_id', null)->groupBy('collection_id')->pluck('collection_id')->toArray();

        $this->info('Found '.count($collection_ids).' collections to import');
        $batchJobs = [];
        Entry::select('id')->where('status', 'PASSED')->where('molecule_id', null)->chunk(10000, function ($ids) use (&$batchJobs) {
            $this->info('Found '.count($ids).' entries to import');
            array_push($batchJobs, new ImportEntriesBatch($ids->pluck('id')->toArray(), 'auto'));
        });
        $this->info('Dispatching import batch...');
        $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {})->catch(function (Batch $batch, Throwable $e) {})->finally(function (Batch $batch) use ($collection_ids) {
            foreach ($collection_ids as $collection_id) {
                Cache::forget('stats.collections'.$collection_id.'entries.count');
                Cache::forget('stats.collections'.$collection_id.'molecules.count');
            }
        })->name('Import Entries Auto')
            ->allowFailures(false)
            ->onConnection('redis')
            ->onQueue('default')
            ->dispatch();
    }
}
