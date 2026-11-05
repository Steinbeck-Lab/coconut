<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollectionAnnotationScores
{
    public const CACHE_KEY = 'collections.avg_annotation_levels';

    /**
     * @var array{0: int, 1: int}
     */
    public const CACHE_TTL = [172800, 259200];

    /**
     * @return Collection<int, float>
     */
    public static function scores(): Collection
    {
        /** @var Collection<int, float> $scores */
        $scores = Cache::flexible(self::CACHE_KEY, self::CACHE_TTL, function () {
            return DB::table('collection_molecule as cm')
                ->join('molecules as m', 'm.id', '=', 'cm.molecule_id')
                ->join('collections as c', 'c.id', '=', 'cm.collection_id')
                ->where('c.status', 'PUBLISHED')
                ->where('m.active', true)
                ->whereRaw('NOT (m.is_parent = true AND m.has_variants = true)')
                ->groupBy('cm.collection_id')
                ->pluck(DB::raw('AVG(m.annotation_level) as avg_score'), 'cm.collection_id')
                ->map(fn ($score) => (float) $score);
        });

        return $scores;
    }

    /**
     * @param  Builder<\App\Models\Collection>  $query
     */
    public static function applySort(Builder $query, string $direction = 'desc'): void
    {
        $sorted = static::scores();
        $sortedIds = ($direction === 'asc' ? $sorted->sort() : $sorted->sortDesc())
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($sortedIds === []) {
            $query->orderByRaw('release_date DESC NULLS LAST');

            return;
        }

        $idList = implode(',', $sortedIds);
        $query->orderByRaw("array_position(ARRAY[{$idList}]::bigint[], collections.id) ASC NULLS LAST");
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
