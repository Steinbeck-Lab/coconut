@props(['taxonomy', 'organismName' => null])

@if (is_array($taxonomy) && ! empty($taxonomy['lineage'] ?? $taxonomy['ranks'] ?? null))
    @php
        $groupKey = strtolower((string) ($taxonomy['biological_group'] ?? ''));
        $groupAccent = match (true) {
            in_array($groupKey, ['fungi', 'fungus'], true) => [
                'iconBg' => 'bg-fuchsia-50',
                'iconText' => 'text-fuchsia-700',
                'chipBg' => 'bg-fuchsia-50',
                'chipText' => 'text-fuchsia-800',
                'chipRing' => 'ring-fuchsia-200',
            ],
            in_array($groupKey, ['plantae', 'plants', 'plant'], true) => [
                'iconBg' => 'bg-emerald-50',
                'iconText' => 'text-emerald-700',
                'chipBg' => 'bg-emerald-50',
                'chipText' => 'text-emerald-800',
                'chipRing' => 'ring-emerald-200',
            ],
            in_array($groupKey, ['animalia', 'animals', 'animal'], true) => [
                'iconBg' => 'bg-sky-50',
                'iconText' => 'text-sky-700',
                'chipBg' => 'bg-sky-50',
                'chipText' => 'text-sky-800',
                'chipRing' => 'ring-sky-200',
            ],
            in_array($groupKey, ['bacteria', 'bacterium'], true) => [
                'iconBg' => 'bg-amber-50',
                'iconText' => 'text-amber-700',
                'chipBg' => 'bg-amber-50',
                'chipText' => 'text-amber-900',
                'chipRing' => 'ring-amber-200',
            ],
            default => [
                'iconBg' => 'bg-slate-100',
                'iconText' => 'text-slate-600',
                'chipBg' => 'bg-slate-100',
                'chipText' => 'text-slate-700',
                'chipRing' => 'ring-slate-200',
            ],
        };
    @endphp

    <div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
        <div class="flex flex-col gap-4 border-b border-gray-100 px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5">
            <div class="flex min-w-0 items-start gap-3">
                <div @class([
                    'flex size-10 shrink-0 items-center justify-center rounded-lg',
                    $groupAccent['iconBg'],
                    $groupAccent['iconText'],
                ])>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.732-3.559" />
                    </svg>
                </div>

                <div class="min-w-0 space-y-1">
                    <p class="text-xs font-medium text-gray-500">Verified taxonomy · Global Names</p>

                    @if (! empty($taxonomy['canonical_name']))
                        <p class="text-lg font-semibold leading-tight text-gray-900">
                            <span class="italic">{{ $taxonomy['canonical_name'] }}</span>
                        </p>
                    @endif

                    @if (! empty($taxonomy['matched_name']) && ($taxonomy['matched_name'] ?? '') !== ($taxonomy['canonical_name'] ?? ''))
                        <p class="text-sm text-gray-600">
                            Matched as <span class="font-medium text-gray-800">{{ $taxonomy['matched_name'] }}</span>
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 sm:justify-end">
                @if (! empty($taxonomy['biological_group_label']))
                    <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                        $groupAccent['chipBg'],
                        $groupAccent['chipText'],
                        $groupAccent['chipRing'],
                    ])>
                        {{ $taxonomy['biological_group_label'] }}
                    </span>
                @endif

                @if (! empty($taxonomy['match_badge']))
                    <span @class([
                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                        'bg-green-50 text-green-800 ring-green-200' => ($taxonomy['match_badge']['tone'] ?? '') === 'success',
                        'bg-amber-50 text-amber-800 ring-amber-200' => ($taxonomy['match_badge']['tone'] ?? '') === 'warning',
                        'bg-gray-50 text-gray-700 ring-gray-200' => ! in_array($taxonomy['match_badge']['tone'] ?? '', ['success', 'warning'], true),
                    ])>
                        @if (($taxonomy['match_badge']['tone'] ?? '') === 'success')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                        {{ $taxonomy['match_badge']['label'] }}
                    </span>
                @endif

                @if (! empty($taxonomy['is_synonym']))
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-800 ring-1 ring-inset ring-red-200">
                        Synonym
                    </span>
                @endif
            </div>
        </div>

        @if (! empty($taxonomy['curation']['original_name']))
            <div class="border-b border-sky-100 bg-sky-50/70 px-4 py-3 sm:px-5">
                <p class="text-sm text-sky-900">
                    Resolved from <span class="font-semibold">{{ $taxonomy['curation']['original_name'] }}</span>
                    via {{ strtolower($taxonomy['curation']['pattern_label'] ?? 'curation') }}
                    @if (! empty($taxonomy['curation']['resolved_lookup']) && ($taxonomy['curation']['resolved_lookup'] ?? '') !== ($taxonomy['curation']['original_name'] ?? ''))
                        (lookup: <span class="font-medium">{{ $taxonomy['curation']['resolved_lookup'] }}</span>)
                    @endif
                </p>
            </div>
        @elseif ($organismName && ! empty($taxonomy['name_differs']))
            <div class="border-b border-amber-100 bg-amber-50/80 px-4 py-3 sm:px-5">
                <p class="flex items-start gap-2 text-sm text-amber-900">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-amber-600" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 6a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0v-3.5A.75.75 0 0 0 10 6Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                    </svg>
                    <span>
                        COCONUT lists <span class="font-semibold">{{ $organismName }}</span>; taxonomy was resolved to the canonical name above.
                    </span>
                </p>
            </div>
        @endif

        @if (! empty($taxonomy['lineage']))
            <div class="border-b border-gray-100 px-4 py-4 sm:px-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Lineage</p>
                <div class="mt-2 flex flex-wrap items-center gap-1 text-sm leading-6">
                    @foreach ($taxonomy['lineage'] as $item)
                        @if ($loop->last)
                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-800">{{ $item['name'] }}</span>
                        @else
                            <span class="rounded-md px-1.5 py-0.5 text-gray-700">{{ $item['name'] }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 shrink-0 text-gray-300" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($taxonomy['ranks']))
            <div class="px-4 py-4 sm:px-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Taxonomic ranks</p>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($taxonomy['ranks'] as $item)
                        <div class="rounded-lg border border-gray-100 bg-gray-50/70 px-3 py-2.5 transition-colors hover:border-gray-200 hover:bg-white">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ $item['rank'] }}</p>
                            <p class="mt-1 truncate text-sm font-medium italic text-gray-900" title="{{ $item['name'] }}">
                                {{ $item['name'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <dl class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                @if (! empty($taxonomy['taxonomic_status']))
                    <div class="flex items-center gap-1.5">
                        <dt class="font-medium text-gray-400">Status</dt>
                        <dd class="font-medium text-gray-700">{{ $taxonomy['taxonomic_status'] }}</dd>
                    </div>
                @endif

                @if (! empty($taxonomy['data_source']))
                    <div class="flex items-center gap-1.5">
                        <dt class="font-medium text-gray-400">Source</dt>
                        <dd>{{ $taxonomy['data_source'] }}</dd>
                    </div>
                @endif

                @if (! empty($taxonomy['fetched_at']))
                    <div class="flex items-center gap-1.5">
                        <dt class="font-medium text-gray-400">Updated</dt>
                        <dd>{{ \Illuminate\Support\Carbon::parse($taxonomy['fetched_at'])->diffForHumans() }}</dd>
                    </div>
                @endif
            </dl>

            @if (! empty($taxonomy['references']))
                <div class="flex flex-wrap gap-2">
                    @foreach ($taxonomy['references'] as $reference)
                        <a href="{{ $reference['url'] }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-secondary-dark shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50 hover:ring-gray-300">
                            {{ $reference['label'] }}
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
