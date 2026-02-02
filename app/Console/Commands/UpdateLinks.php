<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:update-links {collection_id} {old_link} {new_link}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update links for a given collection in entries and collection_molecule tables.';

    /**
     * Escape SQL LIKE wildcard characters (% and _) in a string.
     */
    protected function escapeLikePattern(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collectionId = $this->argument('collection_id');
        $oldPrefix = $this->argument('old_link');
        $newPrefix = $this->argument('new_link');

        $collection = \App\Models\Collection::find($collectionId);
        if (! $collection) {
            $this->error("Collection with ID {$collectionId} not found.");

            return 1;
        }

        $entriesUpdated = 0;
        $moleculesUpdated = 0;
        // Escape SQL wildcards in the old prefix for safe LIKE matching
        $escapedOldPrefix = $this->escapeLikePattern($oldPrefix);

        DB::beginTransaction();
        try {
            // Update entries table
            $entriesUpdated = DB::update(
                "UPDATE entries SET link = REPLACE(link, ?, ?) WHERE collection_id = ? AND link LIKE ? ESCAPE '\\'",
                [$oldPrefix, $newPrefix, $collectionId, $escapedOldPrefix.'%']
            );

            // Update collection_molecule table
            $moleculesUpdated = DB::update(
                "UPDATE collection_molecule SET url = REPLACE(url, ?, ?) WHERE collection_id = ? AND url LIKE ? ESCAPE '\\'",
                [$oldPrefix, $newPrefix, $collectionId, $escapedOldPrefix.'%']
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Update failed: '.$e->getMessage());

            return 1;
        }

        $this->info("Updated $entriesUpdated entries and $moleculesUpdated collection_molecule records.");
    }
}
