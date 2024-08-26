<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use App\Models\Ticker;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AssignIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'molecules:assign-identifiers';

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
        $batchSize = 10000;
        $currentIndex = $this->fetchLastIndex() + 1;
        $data = [];

        // // Step: 1
        // $parents = DB::table('molecules')
        // ->select('id', 'identifier')
        // ->where('has_stereo', false)
        // ->whereNull('identifier')
        // ->get();

        // $parents->chunk($batchSize)->each(function ($moleculesChunk) use (&$currentIndex) {
        //     $data = [];
        //     $header = ['id', 'identifier'];
        //     foreach ($moleculesChunk as $molecule) {
        //         echo($molecule->id . ' - ' . $currentIndex);
        //         echo("\r\n");
        //         if (! $molecule->identifier) {
        //             $data[] = array_combine($header, [$molecule->id, $this->generateIdentifier($currentIndex, 'parent')]);
        //             $currentIndex++;
        //         }
        //     }
        //     $this->insertBatch($data);
        // });

        // $this->info('Mapping parents: Done');

        // // Step: 2
        // $mappings = DB::table('molecules')
        //     ->select('parent_id', 'id')
        //     ->whereNotNull('parent_id')
        //     ->where('has_stereo', true)
        //     ->get()
        //     ->groupBy('parent_id')
        //     ->map(function ($items) {
        //         return $items->pluck('id')->sort()->values()->toArray();
        //     });

        // $identifier_mappings = DB::table('molecules')
        //     ->where('has_stereo', false)
        //     ->where('is_parent', true)
        //     ->pluck('identifier', 'id')
        //     ->toArray();

        // $identifier_mappings = array_map(function($identifier) {
        //     return str_replace('.0', '', $identifier);
        // }, $identifier_mappings);

        // $jsonData = json_encode($mappings, JSON_PRETTY_PRINT);
        // $filePath = storage_path('parent_id_mappings.json');
        // file_put_contents($filePath, $jsonData);

        // $jsonData = json_encode($identifier_mappings, JSON_PRETTY_PRINT);
        // $filePath = storage_path('identifier_mappings.json');
        // file_put_contents($filePath, $jsonData);

        // // Step: 3
        // $mappings = json_decode(file_get_contents(storage_path('parent_id_mappings.json')), true);
        // $identifier_mappings = json_decode(file_get_contents(storage_path('identifier_mappings.json')), true);

        // $bulkUpdateData = [];

        // foreach ($mappings as $parentId => $rowIds) {
        //     if (isset($identifier_mappings[$parentId])) {
        //         $baseIdentifier = $identifier_mappings[$parentId];

        //         foreach ($rowIds as $index => $rowId) {
        //             $newIdentifier = $baseIdentifier . '.' . ($index + 1);

        //             $bulkUpdateData[] = [
        //                 'row_id' => $rowId,
        //                 'identifier' => $newIdentifier
        //             ];
        //         }
        //     }
        // }

        // $batchSize = 10000;
        // $i = 0;
        // Collection::make($bulkUpdateData)->chunk($batchSize)->each(function ($chunk) use (&$i) {
        //     echo($i);
        //     echo("\r\n");
        //     DB::transaction(function () use ($chunk) {
        //         foreach ($chunk as $data) {
        //             DB::table('molecules')
        //                 ->where('id', $data['row_id'])
        //                 ->update(['identifier' => $data['identifier']]);
        //         }
        //     });
        //     $i++;
        // });

        // Mapping miss-matched parent_ids
        // $nullIdentifiers = DB::table('molecules')
        // ->whereNull('identifier')
        // ->get();

        // $mapped_data = array_map('str_getcsv', file(storage_path('Mapped_IDs.csv')));
        // Initialize an associative array to store the key-value pairs
        // $associativeArray = [];

        // // Iterate through the array and map keys to values
        // foreach ($mapped_data as $row) {
        //     if (isset($row[0], $row[1])) {  // Check if both key and value exist
        //         $associativeArray[$row[0]] = $row[1];
        //     }
        // }

        // $nullIdentifiers->chunk(100)->each(function ($chunk) use ($associativeArray) {
        //     foreach ($chunk as $molecule) {
        //         if (isset($associativeArray[$molecule->parent_id])) {
        //             $parentId = $associativeArray[$molecule->parent_id];
        //             echo($molecule->parent_id . ' - '. $parentId);
        //             echo("\r\n");
        //             // Update the row with the parent_id
        //             DB::table('molecules')
        //                 ->where('id', $molecule->id)
        //                 ->update(['parent_id' => $parentId]);
        //         }
        //     }
        // });
        // // $identifier_mappings = json_decode(file_get_contents(storage_path('identifier_mappings.json')), true);

        // $mappings = json_decode(file_get_contents(storage_path('parent_id_mappings.json')), true);
        // $bulkUpdateData = [];
        // foreach ($mappings as $parentId => $rowIds) {
        //     echo($parentId);
        //     echo("\r\n");
        //     $bulkUpdateData[] = [
        //         'row_id' => $parentId
        //     ];
        // }

        // Collection::make($bulkUpdateData)->chunk($batchSize)->each(function ($chunk) use (&$i) {
        //     echo($i);
        //     echo("\r\n");
        //     DB::transaction(function () use ($chunk) {
        //         foreach ($chunk as $data) {
        //             DB::table('molecules')
        //                 ->where('id', $data['row_id'])
        //                 ->update(['has_variants' => true]);
        //         }
        //     });
        //     $i++;
        // });

        $mapped_data = array_map('str_getcsv', file(storage_path('collection_molecule_no_duplicates.csv')));

        Collection::make($mapped_data)->chunk($batchSize)->each(function ($chunk) use (&$i) {
            echo $i;
            echo "\r\n";
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $data) {
                    DB::table('molecules')
                        ->where('id', $data)
                        ->update(['is_placeholder' => false]);
                }
            });
            $i++;
        });
    }

    public function generateIdentifier($index, $type)
    {
        if ($type == 'parent') {
            return 'CNP'.str_pad($index, 7, '0', STR_PAD_LEFT).'.0';
        } else {
            return 'CNP'.str_pad($index, 7, '0', STR_PAD_LEFT);
        }
    }

    public function fetchLastIndex()
    {
        $ticker = Ticker::where('type', 'molecule')->first();

        return (int) $ticker->index;
    }

    /**
     * Insert a batch of data into the database.
     *
     * @return void
     */
    private function insertBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                Molecule::where('id', $row['id'])
                    ->update(['identifier' => $row['identifier']]);
            }
        });
    }
}
