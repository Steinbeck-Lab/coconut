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
    protected $signature = 'coconut:publish-molecules-auto {collection_id=65 : The ID of the collection to import}';

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

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        Log::info("Processing molecules for collection: {$collection->name} (ID: {$collection_id})");

        $query = $collection->molecules()->where('status', 'DRAFT')->whereNotNull('identifier');

        // Get the count of molecules to be processed
        $count = $query->count();

        if ($count > 0) {
            Log::info("Total molecules to be published: {$count}");

            // Process molecules in batches of 30,000
            $query->lazyById(30000)
                ->each(function ($molecule) {
                    $molecule->status = 'APPROVED';
                    $molecule->active = true;
                    $molecule->save();
                });

            Log::info("Successfully processed {$count} molecules.");
        } else {
            Log::info('No molecules to process.');
        }

        // Call artisan command to carry on the metadata fetching process
        Artisan::call('coconut:entries-import-references', [
            'collection_id' => $collection_id,
        ]);

        return 0;
    }
}
