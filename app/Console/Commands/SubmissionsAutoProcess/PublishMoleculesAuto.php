<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PublishMoleculesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:publish-molecules-auto {collection_id=65 : The ID of the collection to import} {--force : Retry processing of molecules with failed status only} {--trigger : Trigger subsequent commands in the processing chain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish draft molecules with identifiers, optionally filtered by collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $forcePublish = $this->option('force');
        $triggerNext = $this->option('trigger');

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        Log::info("Processing molecules for collection: {$collection->name} (ID: {$collection_id})");

        // Base query for draft molecules
        $query = $collection->molecules()
            ->where('status', 'DRAFT')
            ->whereNotNull('identifier');

        // If not forcing, exclude already processed molecules (completed OR failed)
        if (! $forcePublish) {
            $query->where(function ($q) {
                $q->whereNull('curation_status->publish-molecules->status')
                    ->orWhereNotIn('curation_status->publish-molecules->status', ['completed', 'failed']);
            });
        } else {
            // If forcing, only process molecules with failed status
            $query->where('curation_status->publish-molecules->status', 'failed');
        }

        // Get the count of molecules to be processed
        $count = $query->count();

        if ($count > 0) {
            Log::info("Total molecules to be published: {$count}");

            // Process molecules in batches of 30,000
            $query->lazyById(30000)
                ->each(function ($molecule) {
                    try {
                        $molecule->status = 'APPROVED';
                        $molecule->active = true;
                        $molecule->save();

                        // Update curation status
                        updateCurationStatus($molecule->id, 'publish-molecules', 'completed');
                    } catch (\Exception $e) {
                        Log::error("Error publishing molecule {$molecule->id}: ".$e->getMessage());
                        updateCurationStatus($molecule->id, 'publish-molecules', 'failed', $e->getMessage());
                    }
                });

            Log::info("Successfully processed {$count} molecules.");
        } else {
            Log::info('No molecules to process.');
        }

        // Call artisan command to carry on the metadata fetching process
        // if ($triggerNext) {
        //     Artisan::call('coconut:entries-import-references', [
        //         'collection_id' => $collection_id,
        //         '--trigger' => true,
        //     ]);
        // }

        return 0;
    }
}
