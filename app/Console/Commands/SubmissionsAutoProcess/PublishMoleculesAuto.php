<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Console\Command;

class PublishMoleculesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:publish-molecules-auto {--collection_id= : Optional collection ID to process molecules from a specific collection}';

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
        $collectionId = $this->option('collection_id');

        // If collection_id is provided, filter molecules by the collection
        if ($collectionId) {
            $collection = Collection::find($collectionId);

            if (! $collection) {
                $this->error("Collection with ID {$collectionId} not found.");

                return 1;
            }

            $this->info("Processing molecules for collection: {$collection->name} (ID: {$collectionId})");
            $query = $collection->molecules()->where('status', 'DRAFT')->whereNotNull('identifier');
        } else {
            $this->info('Processing all draft molecules with identifiers.');
            $query = Molecule::where('status', 'DRAFT')->whereNotNull('identifier');
        }

        // Get the count of molecules to be processed
        $count = $query->count();

        if ($count > 0) {
            $this->info("Total molecules to be published: {$count}");

            // Process molecules in batches of 30,000
            $query->lazyById(30000)
                ->each(function ($molecule) {
                    $molecule->status = 'APPROVED';
                    $molecule->active = true;
                    $molecule->save();
                });

            $this->info("Successfully processed {$count} molecules.");
        } else {
            $this->info('No molecules to process.');
        }

        return 0;
    }
}
