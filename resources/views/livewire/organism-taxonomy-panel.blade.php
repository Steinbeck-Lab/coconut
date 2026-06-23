<div>
    @if ($taxonomy)
        <x-organism-taxonomy-card :taxonomy="$taxonomy" :organism-name="$organism?->name" />
    @elseif ($organism)
        <div class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500">
            No verified taxonomy is stored for <span class="font-medium text-gray-700">{{ $organism->name }}</span> yet.
            Run <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">php artisan coconut:organisms-map-ogg --backfill</code> to enrich it from Global Names (exact matches only).
        </div>
    @endif
</div>
