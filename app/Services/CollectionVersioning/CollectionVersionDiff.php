<?php

namespace App\Services\CollectionVersioning;

use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Support\Collection as SupportCollection;

class CollectionVersionDiffResult
{
    /**
     * @param  SupportCollection<string, int>  $oldOnlySmilesToMoleculeId
     * @param  SupportCollection<string, int>  $retainedSmilesToMoleculeId
     * @param  SupportCollection<int, string>  $newOnlySmiles
     */
    public function __construct(
        public SupportCollection $oldOnlySmilesToMoleculeId,
        public SupportCollection $retainedSmilesToMoleculeId,
        public SupportCollection $newOnlySmiles,
    ) {}

    public function oldOnlyMoleculeIds(): array
    {
        return $this->oldOnlySmilesToMoleculeId->values()->unique()->values()->all();
    }

    public function retainedMoleculeIds(): array
    {
        return $this->retainedSmilesToMoleculeId->values()->unique()->values()->all();
    }
}

class CollectionVersionDiff
{
    public function compare(Collection $oldCollection, Collection $newCollection): CollectionVersionDiffResult
    {
        $oldSmilesToMoleculeId = $this->oldSmilesToMoleculeIds($oldCollection);
        $newSmiles = $this->newPassedSmiles($newCollection);

        $oldOnly = $oldSmilesToMoleculeId->keys()->diff($newSmiles);
        $retained = $oldSmilesToMoleculeId->keys()->intersect($newSmiles);
        $newOnly = $newSmiles->diff($oldSmilesToMoleculeId->keys())->values();

        return new CollectionVersionDiffResult(
            $oldSmilesToMoleculeId->only($oldOnly->all()),
            $oldSmilesToMoleculeId->only($retained->all()),
            $newOnly,
        );
    }

    public function preview(Collection $oldCollection, Collection $newCollection): array
    {
        $diff = $this->compare($oldCollection, $newCollection);

        return [
            'old_only_count' => $diff->oldOnlySmilesToMoleculeId->count(),
            'retained_count' => $diff->retainedSmilesToMoleculeId->count(),
            'new_only_count' => $diff->newOnlySmiles->count(),
            'revoke_candidate_count' => count($diff->oldOnlyMoleculeIds()),
            'old_only_sample' => $diff->oldOnlySmilesToMoleculeId->keys()->take(10)->values()->all(),
            'new_only_sample' => $diff->newOnlySmiles->take(10)->values()->all(),
        ];
    }

    /**
     * @return SupportCollection<string, int>
     */
    protected function oldSmilesToMoleculeIds(Collection $oldCollection): SupportCollection
    {
        return Entry::query()
            ->where('collection_id', $oldCollection->id)
            ->whereNotNull('molecule_id')
            ->whereNotNull('standardized_canonical_smiles')
            ->get(['molecule_id', 'standardized_canonical_smiles'])
            ->groupBy('standardized_canonical_smiles')
            ->map(fn ($group) => (int) $group->first()->molecule_id);
    }

    /**
     * @return SupportCollection<int, string>
     */
    protected function newPassedSmiles(Collection $newCollection): SupportCollection
    {
        return Entry::query()
            ->where('collection_id', $newCollection->id)
            ->where('status', 'PASSED')
            ->whereNotNull('standardized_canonical_smiles')
            ->pluck('standardized_canonical_smiles')
            ->unique()
            ->values();
    }
}
