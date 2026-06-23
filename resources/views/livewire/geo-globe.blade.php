<div id="geo-globe-root" class="min-h-screen bg-white text-gray-900">
    @vite(['resources/js/geo-globe.js'])

    <script type="application/json" id="geo-globe-data">@json(['countries' => $countryStats])</script>

    <div class="mt-24 pb-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="mb-6 flex flex-col gap-5 rounded-2xl bg-gradient-to-b from-gray-50 to-white px-5 py-5 shadow-sm ring-1 ring-gray-200/70 lg:flex-row lg:items-center lg:justify-between">
            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-0 sm:divide-x sm:divide-gray-200">
                <div class="sm:px-5 sm:first:pl-0">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Countries</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-gray-900">{{ number_format($totals['countries']) }}</dd>
                </div>
                <div class="sm:px-5">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Molecules</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-gray-900">{{ number_format($totals['molecules']) }}</dd>
                </div>
                <div class="sm:px-5">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Organisms</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-gray-900">{{ number_format($totals['organisms']) }}</dd>
                </div>
                <div class="sm:px-5 sm:last:pr-0">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Geo locations</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-gray-900">{{ number_format($totals['geo_locations']) }}</dd>
                </div>
            </dl>

            <div class="inline-flex shrink-0 self-start rounded-xl bg-gray-100 p-1 lg:self-center" role="tablist" aria-label="Globe metric">
                <button type="button" data-metric="molecules" role="tab" aria-selected="true"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition bg-white text-gray-900 shadow-sm">
                    Molecules
                </button>
                <button type="button" data-metric="organisms" role="tab" aria-selected="false"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition text-gray-500 hover:text-gray-700">
                    Organisms
                </button>
                <button type="button" data-metric="geo_locations" role="tab" aria-selected="false"
                    class="rounded-lg px-4 py-2 text-sm font-medium transition text-gray-500 hover:text-gray-700">
                    Geo locations
                </button>
            </div>
        </div>

        <div class="grid lg:grid-cols-[minmax(0,1fr)_320px] gap-6">
            <div class="rounded-2xl overflow-hidden bg-white relative z-0">
                <div id="globeViz" class="relative z-0 w-full h-[520px] sm:h-[620px] lg:h-[720px]"></div>
            </div>

            <div class="space-y-6">
                <div data-hover-panel class="rounded-2xl border border-gray-200 bg-gray-50 p-5 opacity-40 transition">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Hovered country</p>
                    <p data-hover-name class="mt-2 text-xl font-semibold text-gray-900">Move over a country</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">Molecules</dt>
                            <dd data-hover-molecules class="font-medium text-gray-900">—</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">Organisms</dt>
                            <dd data-hover-organisms class="font-medium text-gray-900">—</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">Geo locations</dt>
                            <dd data-hover-geo-locations class="font-medium text-gray-900">—</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-4">Top countries by molecules</p>
                    <ol class="space-y-3">
                        @foreach (collect($countryStats)->take(10) as $country)
                            <li class="flex items-center justify-between gap-3 text-sm text-gray-900">
                                <span class="truncate">
                                    @if ($country['flag'])
                                        {{ $country['flag'] }}
                                    @endif
                                    {{ $country['country'] }}
                                </span>
                                <span class="font-medium tabular-nums">{{ number_format($country['molecules']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>

        <p class="mt-6 text-xs text-gray-500">
            Globe visualization powered by
            <a href="https://globe.gl/example/choropleth-countries/" class="underline hover:text-gray-700" target="_blank" rel="noopener">globe.gl</a>.
            Only geo locations with a resolved country code are included.
        </p>
    </div>
</div>
