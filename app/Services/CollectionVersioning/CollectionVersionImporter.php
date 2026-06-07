<?php

namespace App\Services\CollectionVersioning;

use App\Actions\Coconut\AssignDOI;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CollectionVersionImporter
{
    public function __construct(
        protected ValidateMoleculesBatchWaiter $validateWaiter,
        protected CollectionVersionDiff $diff,
        protected RevokeDroppedMolecules $revokeDropped,
        protected StripCollectionProvenance $stripProvenance,
        protected AssignDOI $assignDoi,
    ) {}

    public function import(Collection $newCollection): array
    {
        if ($newCollection->version_migration_status !== Collection::VERSION_MIGRATION_PENDING
            && $newCollection->version_migration_status !== Collection::VERSION_MIGRATION_PROCESSING) {
            throw new RuntimeException('Collection is not in a version migration state.');
        }

        $oldCollection = $this->resolveOldCollection($newCollection);
        if (! $oldCollection) {
            throw new RuntimeException('Could not resolve previous latest collection in lineage.');
        }

        $newCollection->update(['version_migration_status' => Collection::VERSION_MIGRATION_PROCESSING]);

        try {
            $this->validateWaiter->runAndWait($newCollection);

            $diffResult = $this->diff->compare($oldCollection, $newCollection);

            Artisan::call('coconut:import-molecules', ['collection_id' => $newCollection->id]);

            $this->revokeDropped->revoke(
                $diffResult->oldOnlyMoleculeIds(),
                $oldCollection,
                $newCollection,
                $diffResult,
            );

            $this->stripProvenance->stripAllForCollection($oldCollection->id);

            Artisan::call('coconut:enrich-molecules', ['collection_id' => $newCollection->id]);
            Artisan::call('coconut:publish-molecules', ['collection_id' => $newCollection->id]);
            $this->runPostPublishChain($newCollection->id);

            $this->archiveOldCollection($oldCollection, $newCollection);
            $this->publishNewCollection($newCollection, $oldCollection);

            Cache::forget('collections.'.$newCollection->identifier);
            if (Artisan::all()['coconut:cache'] ?? null) {
                Artisan::call('coconut:cache');
            }

            return [
                'old_collection_id' => $oldCollection->id,
                'new_collection_id' => $newCollection->id,
                'revoked' => count($diffResult->oldOnlyMoleculeIds()),
                'retained' => count($diffResult->retainedMoleculeIds()),
                'new_only' => $diffResult->newOnlySmiles->count(),
            ];
        } catch (\Throwable $e) {
            $newCollection->update(['version_migration_status' => Collection::VERSION_MIGRATION_PENDING]);
            throw $e;
        }
    }

    public function preview(Collection $newCollection): array
    {
        $oldCollection = $this->resolveOldCollection($newCollection);
        if (! $oldCollection) {
            throw new RuntimeException('Could not resolve previous latest collection in lineage.');
        }

        return $this->diff->preview($oldCollection, $newCollection);
    }

    protected function resolveOldCollection(Collection $newCollection): ?Collection
    {
        $rootId = $newCollection->lineageRootId();

        return Collection::query()
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_collection_id', $rootId);
            })
            ->where('id', '!=', $newCollection->id)
            ->where('is_latest', true)
            ->first()
            ?? Collection::query()
                ->where(function ($q) use ($rootId) {
                    $q->where('id', $rootId)->orWhere('parent_collection_id', $rootId);
                })
                ->where('id', '!=', $newCollection->id)
                ->where('version', $newCollection->version - 1)
                ->first();
    }

    protected function runPostPublishChain(int $collectionId): void
    {
        $commands = [
            'coconut:generate-properties-auto',
            'coconut:generate-coordinates-auto',
            'coconut:npclassify',
            'coconut:import-pubchem-data',
            'coconut:fetch-cas-numbers',
        ];

        foreach ($commands as $command) {
            if (Artisan::all()[$command] ?? null) {
                Artisan::call($command, ['collection_id' => $collectionId]);
            }
        }
    }

    protected function archiveOldCollection(Collection $oldCollection, Collection $newCollection): void
    {
        $entriesCount = Entry::query()->where('collection_id', $oldCollection->id)->count();
        $moleculesCount = DB::table('collection_molecule')->where('collection_id', $oldCollection->id)->count();

        Entry::query()
            ->where('collection_id', $oldCollection->id)
            ->update(['is_archived' => true]);

        $oldCollection->update([
            'status' => 'SUPERSEDED',
            'is_latest' => false,
            'superseded_by_collection_id' => $newCollection->id,
            'superseded_at' => now(),
            'archived_entries_count' => $entriesCount,
            'archived_molecules_count' => $moleculesCount,
            'molecules_count' => 0,
        ]);
    }

    protected function publishNewCollection(Collection $newCollection, Collection $oldCollection): void
    {
        $root = $newCollection->lineageRoot();

        Collection::query()
            ->where(function ($q) use ($root) {
                $q->where('id', $root->id)->orWhere('parent_collection_id', $root->id);
            })
            ->where('id', '!=', $newCollection->id)
            ->update(['is_latest' => false]);

        $newCollection->update([
            'status' => 'PUBLISHED',
            'is_latest' => true,
            'version_migration_status' => Collection::VERSION_MIGRATION_COMPLETE,
            'release_date' => now(),
        ]);

        $this->assignDoi->assign($newCollection->fresh());
        $newCollection->fresh()->updateBaseDoiLanding(app(\App\Services\DOI\DOIService::class), $root->fresh());
    }
}
