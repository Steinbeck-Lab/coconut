<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\LoadEntriesBatch;
use App\Models\Entry;
use Artisan;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ProcessEntriesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:entries-process-auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-process the entries using cheminformatics microservice';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $batchJobs = [];

        Entry::select('id')->where('status', 'SUBMITTED')->where('molecule_id', null)->chunk(10000, function ($ids) use (&$batchJobs, &$i) {
            array_push($batchJobs, new LoadEntriesBatch($ids->pluck('id')->toArray()));
        });
        $batch = Bus::batch($batchJobs)
            ->then(function (Batch $batch) {
                Artisan::call('coconut:entries-import-auto');
            })->catch(function (Batch $batch, Throwable $e) {})
            ->finally(function (Batch $batch) {})
            ->name('Process Entries Auto')
            ->allowFailures(false)
            ->onConnection('redis')
            ->onQueue('default')
            ->dispatch();
    }
}
