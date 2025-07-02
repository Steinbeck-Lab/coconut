<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Ticker;
use Illuminate\Console\Command;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishMoleculesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:publish-molecules {collection_id : The ID of the collection to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish draft molecules and assign identifiers to them, optionally filtered by collection';

    protected $stepName = 'publish-molecules';

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

        // Base query for draft molecules
        $query = $collection->molecules()
            ->where('status', 'DRAFT');

        // Get the count of molecules to be processed
        $count = $query->count();

        if ($count > 0) {
            Log::info("Total molecules to be published: {$count}");

            // First, publish the molecules
            $query->lazyById(30000)
                ->each(function ($molecule) {
                    try {
                        $molecule->status = 'APPROVED';
                        $molecule->active = true;
                        $molecule->save();

                        // Update curation status
                        updateCurationStatus($molecule->id, $this->stepName, 'completed');
                    } catch (\Exception $e) {
                        Log::error("Error publishing molecule {$molecule->id}: ".$e->getMessage());
                        updateCurationStatus($molecule->id, $this->stepName, 'failed', $e->getMessage());

                        // Dispatch event for job-level notification
                        \App\Events\PostPublishJobFailed::dispatch(
                            'Publish Molecules Auto',
                            $e,
                            [
                                'molecule_id' => $molecule->id,
                                'identifier' => $molecule->identifier ?? 'Unknown',
                                'step' => $this->stepName,
                            ]
                        );
                    }
                });

            Log::info("Successfully published {$count} molecules.");

            // Now assign identifiers to the published molecules
            Log::info('Starting identifier assignment for published molecules...');
            $this->assignIdentifiers($collection_id);

            // Only publish the collection if it was in DRAFT status
            if ($collection->status == 'DRAFT') {
                $collection->status = 'PUBLISHED';
                $collection->save();
                Log::info("Collection {$collection->name} (ID: {$collection_id}) has been published.");
            }
        } else {
            Log::info('No molecules to process.');
            Log::info('Proceeding to assign identifiers for already published molecules...');

            $this->assignIdentifiers($collection_id);

            // Only publish the collection if it was in DRAFT status
            if ($collection->status == 'DRAFT') {
                $collection->status = 'PUBLISHED';
                $collection->save();
                Log::info("Collection {$collection->name} (ID: {$collection_id}) has been published.");
            }
        }

        return 0;
    }

    /**
     * Assign identifiers to published molecules for the given collection
     */
    private function assignIdentifiers($collection_id)
    {
        Log::info("Assigning identifiers for collection ID: {$collection_id}");

        $batchSize = 10000;
        $startingIndex = $this->fetchLastIndex();
        $currentIndex = $startingIndex;
        Log::info("Starting index for identifier assignment: {$startingIndex}");

        // Step 1: Assign identifiers to parent molecules (molecules without stereo information)
        $parents = DB::table('molecules')
            ->select('molecules.id')
            ->distinct()
            ->join('collection_molecule', 'collection_molecule.molecule_id', '=', 'molecules.id')
            ->where('molecules.has_stereo', false)
            ->whereNull('molecules.identifier')
            ->whereIn('molecules.status', ['APPROVED', 'REVOKED']) // Filter for only published ones
            ->where('collection_molecule.collection_id', $collection_id)
            ->get();

        if ($parents->count() > 0) {
            Log::info("Assigning identifiers to {$parents->count()} parent molecules...");

            $data = [];
            $parents->chunk($batchSize)->each(function ($moleculesChunk) use (&$currentIndex, &$data, $collection_id) {
                $header = ['id', 'identifier'];
                foreach ($moleculesChunk as $molecule) {
                    $currentIndex++;
                    $data[] = array_combine($header, [$molecule->id, $this->generateIdentifier($currentIndex, 'parent')]);
                }
                $this->insertBatch($data, $collection_id);
                $data = []; // Reset for next chunk
            });

            Log::info('Parent molecule identifier assignment: Done');
        }

        // Step 2: Handle stereo variants (molecules with stereo information)

        // First, get distinct parent_ids from the current collection
        $childMolecules = DB::table('molecules as m')
            ->join('collection_molecule as cm', 'm.id', '=', 'cm.molecule_id')
            ->where('cm.collection_id', $collection_id)
            ->whereNull('m.identifier')
            ->whereNotNull('m.parent_id');

        $parentIds = $childMolecules->pluck('m.parent_id')->unique()->filter(function ($id) {
            return ! is_null($id);
        });

        $childrenIds = $childMolecules->pluck('m.id')->unique();

        if ($parentIds->isEmpty()) {
            $mappings = collect();
        } else {
            // Get ALL stereo variants for these parent_ids (not just from current collection)
            $mappings = DB::table('molecules')
                ->select('molecules.parent_id', 'molecules.id', 'molecules.identifier')
                ->whereNotNull('molecules.parent_id')
                ->where('molecules.has_stereo', true)
                ->whereIn('molecules.status', ['APPROVED', 'REVOKED'])
                ->whereIn('molecules.parent_id', $parentIds)
                ->get()
                ->groupBy('parent_id')
                ->map(function ($items) {
                    return $items->sortBy('id')->pluck('identifier', 'id')->toArray();
                });
        }

        if ($mappings->count() > 0) {
            Log::info("Processing stereo variants for {$mappings->count()} parent groups...");

            // Get parent molecule identifiers for the specific parent_ids we're working with
            $identifier_mappings = DB::table('molecules')
                ->where('molecules.has_stereo', false)
                ->where('molecules.is_parent', true)
                ->whereNotNull('molecules.identifier')  // Has existing identifier
                ->whereIn('molecules.id', $parentIds)  // Only get parents we're actually working with
                ->pluck('molecules.identifier', 'molecules.id')
                ->toArray();

            $identifier_mappings = array_map(function ($identifier) {
                return str_replace('.0', '', $identifier);
            }, $identifier_mappings);

            // Create temporary files for processing
            $jsonData = json_encode($mappings, JSON_PRETTY_PRINT);
            $filePath = storage_path("parent_id_mappings_{$collection_id}.json");
            file_put_contents($filePath, $jsonData);

            $jsonData = json_encode($identifier_mappings, JSON_PRETTY_PRINT);
            $filePath = storage_path("identifier_mappings_{$collection_id}.json");
            file_put_contents($filePath, $jsonData);

            // Step 3: Assign identifiers to stereo variants

            $bulkUpdateData = [];

            foreach ($mappings as $parentId => $group) {
                if (isset($identifier_mappings[$parentId])) {
                    $baseIdentifier = $identifier_mappings[$parentId];
                    $nonNullCount = count(array_filter($group, function ($value) {
                        return ! is_null($value);
                    }));

                    foreach ($group as $rowId => $existingIdentifier) {
                        if (is_null($existingIdentifier) && $childrenIds->contains($rowId)) {
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

            if (! empty($bulkUpdateData)) {
                Log::info('Assigning identifiers to '.count($bulkUpdateData).' stereo variant molecules...');

                $i = 0;
                SupportCollection::make($bulkUpdateData)->chunk($batchSize)->each(function ($chunk) use (&$i) {
                    DB::transaction(function () use ($chunk) {
                        foreach ($chunk as $data) {
                            DB::table('molecules')
                                ->where('id', $data['row_id'])
                                ->update(['identifier' => $data['identifier']]);
                        }
                    });
                    $i++;
                });
            }

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
        }

        // Update ticker with final index used
        if ($currentIndex > $startingIndex) {

            $ticker = Ticker::where('type', 'molecule')->first();
            $ticker->index = $currentIndex;
            $ticker->save();
            Log::info("Updated ticker from {$startingIndex} to {$currentIndex}. Used ".($currentIndex - $startingIndex).' new identifiers.');
        }

        Log::info("Identifier assignment completed for collection ID: {$collection_id}");
    }

    /**
     * Generate an identifier based on index and type
     */
    private function generateIdentifier($index, $type)
    {
        if ($type == 'parent') {
            return 'CNP'.str_pad($index, 7, '0', STR_PAD_LEFT).'.0';
        } else {
            return 'CNP'.str_pad($index, 7, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Fetch the last used index from the ticker
     */
    private function fetchLastIndex()
    {
        $maxValue = DB::table('molecules')
            ->selectRaw("MAX((regexp_replace(identifier, '^CNP(\\d+)\\..*$', '\\1'))::int) as max_value")
            ->value('max_value');

        $ticker = Ticker::where('type', 'molecule')->first();

        if ((int) $ticker->index !== (int) $maxValue) {
            Log::info("Ticker index ({$ticker->index}) does not match the maximum molecule identifier value ({$maxValue}).");
            Log::info("Updating ticker index to {$maxValue}.");

            $ticker->index = $maxValue;
            $ticker->save();
            Log::info("Ticker updated to {$maxValue}.");
        }

        return (int) $ticker->index;
    }

    /**
     * Insert a batch of data into the database
     */
    private function insertBatch(array $data, $collection_id)
    {
        if (! empty($data)) {
            DB::transaction(function () use ($data) {
                foreach ($data as $row) {
                    Molecule::where('id', $row['id'])
                        ->update(['identifier' => $row['identifier']]);
                }
            });
        }
    }
}
