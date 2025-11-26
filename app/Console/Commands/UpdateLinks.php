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
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $collectionId = $this->argument('collection_id');
        $oldPrefix = $this->argument('old_link');
        $newPrefix = $this->argument('new_link');

        if (empty($collectionId) || empty($oldPrefix) || empty($newPrefix)) {
            $this->error('Missing required arguments: collection_id, old_link, new_link');
            $this->info('Usage: php artisan coconut:update-links {collection_id} {old_link} {new_link}');

            return 1;
        }

        $entriesUpdated = 0;
        $moleculesUpdated = 0;
        DB::beginTransaction();
        try {
            // Update entries table
            $entriesUpdated = DB::update(
                'UPDATE entries SET link = REPLACE(link, ?, ?) WHERE collection_id = ? AND link LIKE ?', [$oldPrefix, $newPrefix, $collectionId, $oldPrefix.'%']
            );

            // Update collection_molecule table
            $moleculesUpdated = DB::update(
                'UPDATE collection_molecule SET url = REPLACE(url, ?, ?) WHERE collection_id = ? AND url LIKE ?', [$oldPrefix, $newPrefix, $collectionId, $oldPrefix.'%']
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
