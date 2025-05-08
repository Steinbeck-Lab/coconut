<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Molecule;
use App\Models\Ticker;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class AssignIdentifiersAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:molecules-assign-identifiers-auto';

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

        // Step: 1
        $parents = DB::table('molecules')
            ->select('id', 'identifier')
            ->where('has_stereo', false)
            ->whereNull('identifier')
            ->get();

        $data = [];
        $parents->chunk($batchSize)->each(function ($moleculesChunk) use (&$currentIndex, &$data) {
            $header = ['id', 'identifier'];
            foreach ($moleculesChunk as $molecule) {
                echo $molecule->id.' - '.$currentIndex;
                echo "\r\n";
                if (! $molecule->identifier) {
                    $data[] = array_combine($header, [$molecule->id, $this->generateIdentifier($currentIndex, 'parent')]);
                    $currentIndex++;
                }
            }
            $this->insertBatch($data);
        });

        $this->info('Mapping parents: Done');

        // // Step: 2
        $mappings = DB::table('molecules')
            ->select('parent_id', 'id', 'identifier')
            ->whereNotNull('parent_id')
            ->where('has_stereo', true)
            ->get()
            ->groupBy('parent_id')
            ->map(function ($items) {
                return $items->sortBy('id')->pluck('identifier', 'id')->toArray();
            });

        $identifier_mappings = DB::table('molecules')
            ->where('has_stereo', false)
            ->where('is_parent', true)
            ->pluck('identifier', 'id')
            ->toArray();

        $identifier_mappings = array_map(function ($identifier) {
            return str_replace('.0', '', $identifier);
        }, $identifier_mappings);

        $jsonData = json_encode($mappings, JSON_PRETTY_PRINT);
        $filePath = storage_path('parent_id_mappings.json');
        file_put_contents($filePath, $jsonData);

        $jsonData = json_encode($identifier_mappings, JSON_PRETTY_PRINT);
        $filePath = storage_path('identifier_mappings.json');
        file_put_contents($filePath, $jsonData);

        // Step: 3
        $mappings = json_decode(file_get_contents(storage_path('parent_id_mappings.json')), true);
        $identifier_mappings = json_decode(file_get_contents(storage_path('identifier_mappings.json')), true);

        $bulkUpdateData = [];

        foreach ($mappings as $parentId => $group) {
            if (isset($identifier_mappings[$parentId])) {
                $baseIdentifier = $identifier_mappings[$parentId];
                $nonNullCount = count(array_filter($group, function ($value) {
                    return ! is_null($value);
                }));

                foreach ($group as $rowId => $existingIdentifier) {
                    if (is_null($existingIdentifier)) {
                        $nonNullCount++;
                        $newIdentifier = $baseIdentifier.'.'.$nonNullCount;

                        $bulkUpdateData[] = [
                            'row_id' => $rowId,
                            'identifier' => $newIdentifier,
                        ];
                    }
                }
            }
        }

        $batchSize = 10000;
        $i = 0;
        Collection::make($bulkUpdateData)->chunk($batchSize)->each(function ($chunk) use (&$i) {
            echo $i;
            echo "\r\n";
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $data) {
                    DB::table('molecules')
                        ->where('id', $data['row_id'])
                        ->update(['identifier' => $data['identifier']]);
                }
            });
            $i++;
        });

        Artisan::call('coconut:publish-molecules-auto');
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
        $maxValue = DB::table('molecules')
            ->selectRaw("MAX((regexp_replace(identifier, '^CNP(\\d+)\\..*$', '\\1'))::int) as max_value")
            ->value('max_value');

        $ticker = Ticker::where('type', 'molecule')->first();

        if ((int) $ticker->index !== (int) $maxValue) {
            $this->info("Ticker index ({$ticker->index}) does not match the maximum molecule identifier value ({$maxValue}).");
            $this->info("Updating ticker index to {$maxValue}.");

            $ticker->index = $maxValue;
            $ticker->save();
            $this->info("Ticker updated to {$maxValue}.");
        }

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
