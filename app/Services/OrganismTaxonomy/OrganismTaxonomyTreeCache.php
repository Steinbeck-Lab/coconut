<?php

namespace App\Services\OrganismTaxonomy;

use Illuminate\Support\Facades\Cache;

class OrganismTaxonomyTreeCache
{
    private const CACHE_KEY = 'tree-of-life.taxonomy';

    /**
     * @return array{tree: array<string, mixed>, index: array<string, array<string, mixed>>}
     */
    public function get(OrganismTaxonomyTreeBuilder $builder): array
    {
        $store = (string) config('services.organism_taxonomy.tree_cache_store', 'file');

        return Cache::store($store)->remember(
            self::CACHE_KEY,
            now()->addHours((int) config('services.organism_taxonomy.tree_cache_hours', 2)),
            fn (): array => $builder->build(),
        );
    }

    public function forget(): void
    {
        $store = (string) config('services.organism_taxonomy.tree_cache_store', 'file');

        Cache::store($store)->forget(self::CACHE_KEY);

        // Clear legacy copies that may have been written to the default store.
        Cache::forget(self::CACHE_KEY);
    }
}
