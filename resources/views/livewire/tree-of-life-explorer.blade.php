<div>
    <div class="mt-32 min-h-screen pb-24">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">Natural product sources</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Tree of life explorer</h1>
                <p class="mt-4 text-lg text-gray-600">
                    Browse taxonomic groups linked to COCONUT source organisms and explore how natural products
                    are distributed across kingdoms, phyla, families, and other ranks.
                </p>
            </div>

            <dl class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow ring-1 ring-gray-200">
                    <dt class="truncate text-sm font-medium text-gray-500">Unique molecules with a source organism</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalMoleculesWithOrganisms) }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow ring-1 ring-gray-200">
                    <dt class="truncate text-sm font-medium text-gray-500">Source organisms in COCONUT</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalSourceOrganisms) }}</dd>
                </div>
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow ring-1 ring-gray-200">
                    <dt class="truncate text-sm font-medium text-gray-500">Taxonomically classified</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $classifiedPercent }}%</dd>
                </div>
            </dl>

            <nav class="mt-8 flex flex-wrap items-center gap-2 text-sm text-gray-600" aria-label="Taxonomic path">
                <button type="button" wire:click="selectNode('root')"
                    class="rounded-md px-2 py-1 hover:bg-gray-100 @if($selectedNodeId === null) font-semibold text-emerald-700 @endif">
                    All life
                </button>
                @foreach ($breadcrumb as $crumb)
                    <span aria-hidden="true">/</span>
                    <span class="capitalize text-gray-400">{{ $crumb['rank'] }}</span>
                    <span class="font-medium text-gray-900">{{ $crumb['name'] }}</span>
                @endforeach
            </nav>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach (['children' => 'Direct children', 'kingdom' => 'Kingdom', 'phylum' => 'Phylum', 'class' => 'Class', 'order' => 'Order', 'family' => 'Family', 'genus' => 'Genus'] as $rank => $label)
                    <button type="button" wire:click="setDistributionRank('{{ $rank }}')"
                        class="rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset transition
                        @if($distributionRank === $rank) bg-emerald-600 text-white ring-emerald-600 @else bg-white text-gray-700 ring-gray-300 hover:bg-gray-50 @endif">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-12">
                <aside class="lg:col-span-4">
                    <div class="rounded-xl bg-white p-5 shadow ring-1 ring-gray-200">
                        <h2 class="text-base font-semibold text-gray-900">Browse taxonomy</h2>
                        <p class="mt-1 text-sm text-gray-500">Select a group to drill down the tree of life.</p>
                        <div class="mt-4 max-h-[32rem] overflow-y-auto pr-1">
                            @include('components.taxonomy-tree-branch', [
                                'nodes' => $tree['children'] ?? [],
                                'depth' => 0,
                                'selectedNodeId' => $selectedNodeId,
                            ])
                        </div>
                    </div>
                </aside>

                <section class="lg:col-span-8 space-y-8">
                    <div class="rounded-xl bg-white p-5 shadow ring-1 ring-gray-200">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">
                                    Molecule distribution
                                    @if ($selectedNode['id'] !== 'root')
                                        <span class="font-normal text-gray-500">under {{ $selectedNode['name'] }}</span>
                                    @endif
                                </h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ number_format($selectedNode['molecule_count']) }} molecule–organism links across
                                    {{ number_format($selectedNode['organism_count']) }} source organisms in this branch.
                                    @if ($selectedNode['id'] === 'root')
                                        <span class="text-gray-400">({{ number_format($totalMoleculesWithOrganisms) }} unique molecules in COCONUT.)</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @php
                            $maxCount = max(1, collect($distribution)->max('molecule_count') ?? 1);
                            $topDistribution = array_slice($distribution, 0, 25);
                        @endphp

                        @if ($topDistribution === [])
                            <p class="mt-6 text-sm text-gray-500">No distribution data for this selection yet.</p>
                        @else
                            <div class="mt-6" wire:key="treemap-{{ $selectedNodeId }}-{{ $distributionRank }}">
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Treemap view</p>
                                <div class="relative h-80 w-full rounded-lg border border-gray-100 bg-gray-50 p-2" wire:ignore>
                                    <canvas id="tol-treemap-canvas" aria-label="Taxonomic distribution treemap"></canvas>
                                </div>
                                <script type="application/json" id="tol-treemap-data">@json($treemapItems)</script>
                            </div>

                            <div class="mt-8 space-y-3" wire:key="distribution-{{ $selectedNodeId }}-{{ $distributionRank }}">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Ranked list</p>
                                @foreach ($topDistribution as $item)
                                    @php $width = round(($item['molecule_count'] / $maxCount) * 100, 1); @endphp
                                    <button type="button" wire:click="selectNode('{{ $item['id'] }}')"
                                        class="group w-full rounded-lg border border-transparent p-2 text-left hover:border-emerald-200 hover:bg-emerald-50/50">
                                        <div class="flex items-center justify-between gap-4 text-sm">
                                            <span class="font-medium text-gray-900 group-hover:text-emerald-800">
                                                {{ $item['name'] }}
                                                @if (! empty($item['rank']) && $item['rank'] !== 'children')
                                                    <span class="ml-1 text-xs font-normal capitalize text-gray-400">{{ $item['rank'] }}</span>
                                                @endif
                                            </span>
                                            <span class="shrink-0 tabular-nums text-gray-600">
                                                {{ number_format($item['molecule_count']) }} NP
                                                <span class="text-gray-400">· {{ number_format($item['organism_count']) }} org.</span>
                                            </span>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full bg-emerald-500 transition-all" style="width: {{ $width }}%"></div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="rounded-xl bg-white p-5 shadow ring-1 ring-gray-200">
                        <h2 class="text-base font-semibold text-gray-900">Source organisms</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Top organisms in this branch ranked by linked natural products.
                        </p>

                        @if ($organisms->isEmpty())
                            <p class="mt-6 text-sm text-gray-500">No organisms in this branch.</p>
                        @else
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500">
                                            <th class="py-2 pr-4 font-medium">Organism</th>
                                            <th class="py-2 pr-4 font-medium text-right">Molecules</th>
                                            <th class="py-2 font-medium text-right">Search</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($organisms as $organism)
                                            <tr class="hover:bg-gray-50">
                                                <td class="py-2 pr-4 font-medium text-gray-900">{{ $organism['name'] }}</td>
                                                <td class="py-2 pr-4 text-right tabular-nums text-gray-700">
                                                    {{ number_format($organism['molecule_count']) }}
                                                </td>
                                                <td class="py-2 text-right">
                                                    <a href="/search?type=tags&amp;q={{ urlencode($organism['name']) }}&amp;tagType=organisms"
                                                        class="text-emerald-700 hover:text-emerald-900">
                                                        View NPs
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@2.3.1/dist/chartjs-chart-treemap.min.js"></script>
@endassets

@script
<script nonce="{{ csp_nonce() }}">
    const tolTreemapColors = [
        '#059669', '#10b981', '#34d399', '#6ee7b7',
        '#047857', '#065f46', '#0d9488', '#14b8a6',
        '#2dd4bf', '#5eead4', '#99f6e4', '#ccfbf1',
    ];

    let tolTreemapChart = null;

    function tolTreemapItems() {
        const node = document.getElementById('tol-treemap-data');

        if (! node) {
            return [];
        }

        try {
            return JSON.parse(node.textContent || '[]');
        } catch (error) {
            return [];
        }
    }

    function destroyTolTreemap() {
        if (tolTreemapChart) {
            tolTreemapChart.destroy();
            tolTreemapChart = null;
        }
    }

    function renderTolTreemap() {
        const canvas = document.getElementById('tol-treemap-canvas');
        const items = tolTreemapItems();

        if (! canvas || items.length === 0 || typeof Chart === 'undefined') {
            destroyTolTreemap();

            return;
        }

        destroyTolTreemap();

        const tree = items.map((item) => ({
            name: item.name,
            value: item.value,
            organisms: item.organisms,
            nodeId: item.id,
        }));

        tolTreemapChart = new Chart(canvas, {
            type: 'treemap',
            data: {
                datasets: [{
                    label: 'Natural products',
                    tree,
                    key: 'value',
                    groups: ['name'],
                    spacing: 1,
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    backgroundColor: (context) => {
                        if (context.type !== 'data') {
                            return 'transparent';
                        }

                        return tolTreemapColors[context.dataIndex % tolTreemapColors.length];
                    },
                    labels: {
                        display: true,
                        formatter: (context) => {
                            const item = context.raw;

                            if (! item || ! item.g) {
                                return '';
                            }

                            return [item.g, `${item.v} NP`];
                        },
                        color: ['#ffffff', 'rgba(255,255,255,0.85)'],
                        font: [{ size: 13, weight: 'bold' }, { size: 11 }],
                    },
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => items[0]?.raw?.g ?? '',
                            label: (item) => {
                                const organisms = item.raw?._data?.organisms;

                                return [
                                    `${item.raw.v} molecules`,
                                    organisms ? `${organisms} source organisms` : null,
                                ].filter(Boolean);
                            },
                        },
                    },
                },
                onClick: (_event, elements) => {
                    if (! elements.length) {
                        return;
                    }

                    const nodeId = elements[0].element.$context.raw._data?.nodeId;

                    if (nodeId) {
                        $wire.selectNode(nodeId);
                    }
                },
            },
        });
    }

    renderTolTreemap();

    Livewire.hook('commit', ({ component, succeed }) => {
        succeed(() => {
            if (component.name === 'tree-of-life-explorer') {
                queueMicrotask(renderTolTreemap);
            }
        });
    });
</script>
@endscript
