<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLinks extends Command
{
    protected $signature = 'coconut:update-links {collection_id} {old_link} {new_link}';

    protected $description = 'Update links for a given collection in entries and collection_molecule tables.';

    public function handle()
    {
        $collectionId = $this->argument('collection_id');
        $oldPrefix = $this->argument('old_link');
        $newPrefix = $this->argument('new_link');

        // Update entries table
        $entriesUpdated = DB::table('entries')
            ->where('collection_id', $collectionId)
            ->where('link', 'like', $oldPrefix.'%')
            ->update([
                'link' => DB::raw("REPLACE(link, '$oldPrefix', '$newPrefix')"),
            ]);

        // Update collection_molecule table
        $moleculesUpdated = DB::table('collection_molecule')
            ->where('collection_id', $collectionId)
            ->where('url', 'like', $oldPrefix.'%')
            ->update([
                'url' => DB::raw("REPLACE(url, '$oldPrefix', '$newPrefix')"),
            ]);

        $this->info("Updated $entriesUpdated entries and $moleculesUpdated collection_molecule records.");
    }
}
