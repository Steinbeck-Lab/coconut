<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishMolecules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:publish-molecules {collection_id?}';

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
        // Grab the 'collection_id' argument, which may be null or an integer
        $collection_id = $this->argument('collection_id');

        // Decide which query to build based on whether collection_id is null
        if (! is_null($collection_id)) {
            // Attempt to find the specified collection
            $collection = Collection::find($collection_id);

            if (! $collection) {
                // If the collection doesn’t exist, you can either exit or handle differently
                $this->error("Collection with ID {$collection_id} not found.");

                return;
            }

            // Retrieve only molecules from this collection that do not have properties
            $query = $collection->molecules();
        }

        // Use chunk to process large sets of molecules
        $query->select('molecules.id')
            ->where('status', 'DRAFT')
            ->chunk(30000, function ($mols) use (&$i) {
                $ids = $mols->pluck('id')->toArray();

                DB::table('molecules')
                    ->whereIn('id', $ids)
                    ->update([
                        'status' => 'APPROVED',
                        'active' => true,
                    ]);
            });

        $collection->update([
            'status' => 'PUBLISHED',
        ]);
    }
}
