<?php

namespace App\Services\CollectionVersioning;

use Illuminate\Support\Facades\DB;

class StripCollectionProvenance
{
    public function stripAllForCollection(int $oldCollectionId): void
    {
        $moleculeIds = DB::table('collection_molecule')
            ->where('collection_id', $oldCollectionId)
            ->pluck('molecule_id')
            ->unique()
            ->all();

        foreach ($moleculeIds as $moleculeId) {
            $this->stripForMolecule((int) $moleculeId, $oldCollectionId);
        }

        DB::table('citables')
            ->where('citable_type', 'App\\Models\\Collection')
            ->where('citable_id', $oldCollectionId)
            ->delete();
    }

    public function stripForMolecule(int $moleculeId, int $oldCollectionId): void
    {
        DB::table('collection_molecule')
            ->where('collection_id', $oldCollectionId)
            ->where('molecule_id', $moleculeId)
            ->delete();

        $rows = DB::table('molecule_organism')
            ->where('molecule_id', $moleculeId)
            ->get();

        foreach ($rows as $row) {
            $metadata = json_decode($row->metadata ?? '{}', true) ?: [];
            $cols = array_values(array_filter(
                $metadata['cols'] ?? [],
                fn ($col) => (int) ($col['id'] ?? 0) !== $oldCollectionId
            ));

            $collectionIds = array_values(array_filter(
                json_decode($row->collection_ids ?? '[]', true) ?: [],
                fn ($id) => (int) $id !== $oldCollectionId
            ));

            if (empty($cols) && empty($collectionIds)) {
                DB::table('molecule_organism')->where('id', $row->id)->delete();

                continue;
            }

            $metadata['cols'] = $cols;
            $metadata['col_ids'] = array_values(array_filter(
                $metadata['col_ids'] ?? [],
                fn ($id) => (int) $id !== $oldCollectionId
            ));

            DB::table('molecule_organism')->where('id', $row->id)->update([
                'metadata' => json_encode($metadata),
                'collection_ids' => json_encode($collectionIds),
                'updated_at' => now(),
            ]);
        }

        $entryDois = DB::table('entries')
            ->where('collection_id', $oldCollectionId)
            ->where('molecule_id', $moleculeId)
            ->whereNotNull('doi')
            ->pluck('doi')
            ->flatMap(fn ($doi) => array_filter(array_map('trim', preg_split('/[|,]/', (string) $doi))))
            ->unique()
            ->all();

        if (! empty($entryDois)) {
            $citationIds = DB::table('citations')->whereIn('doi', $entryDois)->pluck('id');
            if ($citationIds->isNotEmpty()) {
                DB::table('citables')
                    ->where('citable_type', 'App\\Models\\Molecule')
                    ->where('citable_id', $moleculeId)
                    ->whereIn('citation_id', $citationIds)
                    ->delete();
            }
        }
    }
}
