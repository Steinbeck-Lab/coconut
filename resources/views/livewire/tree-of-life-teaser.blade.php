
<section class="bg-gradient-to-b from-white to-emerald-50/40 py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">Explore source diversity</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Tree of life</h2>
                <p class="mt-4 text-lg text-gray-600">
                    See how natural products in COCONUT are distributed across taxonomic kingdoms and drill down
                    into phyla, families, and source organisms.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="/tree-of-life"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                        Open explorer
                    </a>
                    <a href="/tree-of-life?rank=phylum"
                        class="inline-flex items-center rounded-md bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50">
                        Browse by phylum
                    </a>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-gray-200">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Kingdom distribution</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ number_format($totalMolecules) }} unique molecules with a source organism
                        </p>
                    </div>
                </div>

                @if ($kingdoms === [])
                    <p class="mt-6 text-sm text-gray-500">
                        Taxonomy data is not available yet. Run organism mapping to populate the tree.
                    </p>
                @else
                    <div class="mt-6 space-y-3">
                        @foreach ($kingdoms as $kingdom)
                            @php $width = round(($kingdom['molecule_count'] / $maxCount) * 100, 1); @endphp
                            <a href="/tree-of-life?node={{ urlencode($kingdom['id']) }}"
                                class="group block rounded-lg border border-transparent p-2 hover:border-emerald-200 hover:bg-emerald-50/60">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-gray-900 group-hover:text-emerald-800">{{ $kingdom['name'] }}</span>
                                    <span class="tabular-nums text-gray-600">{{ number_format($kingdom['molecule_count']) }} NP</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $width }}%"></div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
