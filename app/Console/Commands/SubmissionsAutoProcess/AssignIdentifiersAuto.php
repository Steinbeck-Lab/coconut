<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Ticker;
use Illuminate\Console\Command;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignIdentifiersAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:molecules-assign-identifiers-auto {collection_id=65 : The ID of the collection to process} {--trigger : Trigger subsequent commands in the processing chain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-assign identifiers to molecules for a specific collection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $triggerNext = $this->option('trigger');

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        $this->info("Assigning identifiers for collection ID: {$collection_id}");

        $batchSize = 10000;
        $currentIndex = $this->fetchLastIndex() + 1;

        // Step: 1
        $parents = DB::table('molecules')
            ->select('molecules.id', 'molecules.identifier')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('molecules.has_stereo', false)
            ->whereNull('molecules.identifier')
            ->where('entries.collection_id', $collection_id)
            ->distinct()
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

        // Step: 2
        $mappings = DB::table('molecules')
            ->select('molecules.parent_id', 'molecules.id', 'molecules.identifier')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->whereNotNull('molecules.parent_id')
            ->where('molecules.has_stereo', true)
            ->where('entries.collection_id', $collection_id)
            ->distinct()
            ->get()
            ->groupBy('parent_id')
            ->map(function ($items) {
                return $items->sortBy('id')->pluck('identifier', 'id')->toArray();
            });

        $identifier_mappings = DB::table('molecules')
            ->join('entries', 'entries.molecule_id', '=', 'molecules.id')
            ->where('molecules.has_stereo', false)
            ->where('molecules.is_parent', true)
            ->where('entries.collection_id', $collection_id)
            ->distinct()
            ->pluck('molecules.identifier', 'molecules.id')
            ->toArray();

        $identifier_mappings = array_map(function ($identifier) {
            return str_replace('.0', '', $identifier);
        }, $identifier_mappings);

        $jsonData = json_encode($mappings, JSON_PRETTY_PRINT);
        $filePath = storage_path("parent_id_mappings_{$collection_id}.json");
        file_put_contents($filePath, $jsonData);

        $jsonData = json_encode($identifier_mappings, JSON_PRETTY_PRINT);
        $filePath = storage_path("identifier_mappings_{$collection_id}.json");
        file_put_contents($filePath, $jsonData);

        // Step: 3
        $mappings = json_decode(file_get_contents(storage_path("parent_id_mappings_{$collection_id}.json")), true);
        $identifier_mappings = json_decode(file_get_contents(storage_path("identifier_mappings_{$collection_id}.json")), true);

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
        SupportCollection::make($bulkUpdateData)->chunk($batchSize)->each(function ($chunk) use (&$i, $collection_id) {
            echo $i;
            echo "\r\n";
            DB::transaction(function () use ($chunk, $collection_id) {
                foreach ($chunk as $data) {
                    DB::table('molecules')
                        ->where('id', $data['row_id'])
                        ->update(['identifier' => $data['identifier']]);
                    DB::table('entries')
                        ->where('molecule_id', $data['row_id'])
                        ->where('collection_id', $collection_id)
                        ->update(['identifier' => $data['identifier']]);
                }
            });
            $i++;
        });

        Log::info("Identifier assignment completed for collection ID: {$collection_id}");

        // Clean up temporary files
        $parentMappingsFile = storage_path("parent_id_mappings_{$collection_id}.json");
        $identifierMappingsFile = storage_path("identifier_mappings_{$collection_id}.json");

        if (file_exists($parentMappingsFile)) {
            unlink($parentMappingsFile);
        }

        if (file_exists($identifierMappingsFile)) {
            unlink($identifierMappingsFile);
        }

        Log::info('Temporary files cleaned up.');
        Log::info("Identifier assignment completed for collection ID: {$collection_id}");

        if ($triggerNext) {
            Artisan::call('coconut:publish-molecules-auto', ['collection_id' => $collection_id, '--trigger' => true]);
        }
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
