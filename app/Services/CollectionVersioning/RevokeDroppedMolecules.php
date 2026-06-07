<?php

namespace App\Services\CollectionVersioning;

use App\Models\Collection;
use App\Models\CollectionVersionRevocation;
use App\Models\Entry;
use App\Models\Molecule;
use Illuminate\Support\Facades\DB;

class RevokeDroppedMolecules
{
    /**
     * @param  array<int>  $moleculeIds
     * @return array<int> Revoked molecule ids
     */
    public function revoke(
        array $moleculeIds,
        Collection $oldCollection,
        Collection $newCollection,
        CollectionVersionDiffResult $diff,
    ): array {
        $revoked = [];
        $lineageRootId = $oldCollection->lineageRootId();
        $lineageCollectionIds = $this->lineageCollectionIds($lineageRootId);

        foreach ($moleculeIds as $moleculeId) {
            if (! $this->isExclusiveToLineage($moleculeId, $lineageCollectionIds)) {
                continue;
            }

            $molecule = Molecule::query()->find($moleculeId);
            if (! $molecule) {
                continue;
            }

            $molecule->status = 'REVOKED';
            $molecule->active = false;
            $molecule->comment = trim(($molecule->comment ?? '').' Revoked: dropped in collection version '.$newCollection->version.'.');
            $molecule->save();

            $entry = Entry::query()
                ->where('collection_id', $oldCollection->id)
                ->where('molecule_id', $moleculeId)
                ->first();

            $smiles = $diff->oldOnlySmilesToMoleculeId->search($moleculeId) ?: $entry?->standardized_canonical_smiles;

            CollectionVersionRevocation::query()->create([
                'lineage_root_id' => $lineageRootId,
                'from_collection_id' => $oldCollection->id,
                'to_collection_id' => $newCollection->id,
                'entry_id' => $entry?->id,
                'molecule_id' => $moleculeId,
                'reference_id' => $entry?->reference_id,
                'standardized_canonical_smiles' => is_string($smiles) ? $smiles : null,
                'revoked_at' => now(),
                'reason' => 'dropped_in_version_'.$newCollection->version,
            ]);

            $revoked[] = $moleculeId;
        }

        return $revoked;
    }

    /**
     * @return array<int>
     */
    protected function lineageCollectionIds(int $lineageRootId): array
    {
        return Collection::query()
            ->where(function ($q) use ($lineageRootId) {
                $q->where('id', $lineageRootId)->orWhere('parent_collection_id', $lineageRootId);
            })
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int>  $lineageCollectionIds
     */
    protected function isExclusiveToLineage(int $moleculeId, array $lineageCollectionIds): bool
    {
        $otherCollectionLinks = DB::table('collection_molecule')
            ->where('molecule_id', $moleculeId)
            ->whereNotIn('collection_id', $lineageCollectionIds)
            ->exists();

        if ($otherCollectionLinks) {
            return false;
        }

        $organismRows = DB::table('molecule_organism')
            ->where('molecule_id', $moleculeId)
            ->get(['collection_ids']);

        foreach ($organismRows as $row) {
            $ids = json_decode($row->collection_ids ?? '[]', true) ?: [];
            foreach ($ids as $collectionId) {
                if (! in_array((int) $collectionId, $lineageCollectionIds, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
