<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ClassifyMoleculeBatch;
use App\Models\Molecule;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyAuto extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:npclassify-auto';

    /**
     * The console command description.
     */
    protected $description = 'Classifies molecules using NPClassifier for all molecules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = Molecule::select('molecules.id', 'molecules.canonical_smiles')->whereHas('properties', function ($q) {
            $q->whereNull('np_classifier_pathway')->whereNull('np_classifier_superclass')->whereNull('np_classifier_class')->whereNull('np_classifier_is_glycoside');
        });
        $this->info('Processing all molecules that need classification.');

        // Count the total number of molecules
        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info('No molecules found to classify.');

            return 0;
        }

        $this->info("Total molecules to process: {$totalCount}");

        // Process molecules in chunks and create batch jobs
        $query->chunkById(1000, function ($molecules) {
            $batchJobs = [];
            $moleculeIds = $molecules->pluck('id')->toArray();
            $batchJobs[] = new ClassifyMoleculeBatch($moleculeIds);

            // Dispatch the batch
            Bus::batch($batchJobs)
                ->then(function (Batch $batch) {
                    Log::info('NPClassifier batch completed: '.$batch->id);
                    Log::info('Calling GenerateCoordinatesAuto after NPClassifier batch');
                    Artisan::call('coconut:generate-coordinates-auto');
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    Log::error('NPClassifier batch failed: '.$e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    // Cleanup or logging can happen here
                })
                ->name('NPClassifier Batch Auto')
                ->allowFailures(true)
                ->onConnection('redis')
                ->onQueue('default')
                ->dispatch();
        });
        $this->info('All classification jobs have been dispatched!');

        return 0;
    }
}
