<!-- molecule-history-timeline.blade.php -->
<div>
    <div x-data="{ 
        on: false, 
        event: '', 
        column: '', 
        drawerOpen: false, 
        drawerContent: '',
    }"
        x-effect="on && $wire.getHistory()"
        class="mt-5">
        <button type="button"
            x-on:click="on = !on"
            class="text-base font-semibold text-gray-700 hover:text-gray-900 transition-colors inline-flex items-center gap-1"
            wire:transition="fade">
            <span x-show="!on">View complete history</span>
            <span x-show="on">View complete history</span>
            <svg x-show="!on" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <svg x-show="on" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div x-show="on" class="mt-3 bg-white px-5 py-5 shadow-sm rounded-xl border border-gray-200">
            <div class="lg:col-start-3">
                <!-- Activity feed -->
                <ul role="list" class="space-y-4">
                    @foreach ($audit_data as $audit)
                    @if (array_key_exists('affected_columns', $audit))
                    <li class="relative flex gap-x-3">
                        <div class="absolute -bottom-6 left-0 top-0 flex w-6 justify-center">
                            <div class="w-px bg-gray-100"></div>
                        </div>
                        <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                            <div class="h-2 w-2 rounded-full bg-gray-200"></div>
                        </div>
                        <div class="flex-auto">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-medium text-gray-700">{{ $audit['user_name'] ?? 'COCONUT Curator' }}</span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-50 rounded-full border border-gray-200 capitalize">
                                    {{ $audit['event'] ?? '' }}
                                    <span class="text-gray-400">{{ \Carbon\Carbon::parse($audit['created_at'])->diffForHumans() }}</span>
                                </span>
                            </div>

                            <div class="mt-2 space-y-1">
                                @foreach ($audit['affected_columns'] as $column_name => $column_values)
                                <div 
                                    @click="
                                        drawerOpen = true; 
                                        event = '{{ $audit['event'] }}';
                                        column = '{{ $column_name }}';
                                        drawerContent = JSON.parse($el.dataset.content)"
                                    data-content="{{ e(json_encode($column_values, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)) }}"
                                    class="text-sm text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-md px-2 py-1 -ml-2 cursor-pointer transition-colors">
                                    {{ Str::of($column_name)->camel()->replace('_', ' ')->replaceMatches('/[A-Z]/', ' $0')->title() }}
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </li>
                    @endif
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Drawer -->
        <div x-show="drawerOpen" class="fixed inset-0 z-50 flex">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="drawerOpen = false"></div>

            <!-- Drawer panel -->
            <div class="relative bg-white w-full max-w-md ml-auto shadow-2xl transform transition-all">
                <div class="flex justify-between items-center px-5 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 capitalize" x-text="event"></h2>
                    <button @click="drawerOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                        <span class="sr-only">Close panel</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-5">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Column</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Old Value</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">New Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900" x-text="column"></td>

                                <!-- Old Value -->
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    <template x-if="['2d', '3d'].includes(column)">
                                        <pre class="whitespace-pre-wrap text-xs overflow-auto max-h-40" x-text="drawerContent?.old_value || 'N/A'"></pre>
                                    </template>
                                    <template x-if="column === 'iupac_name'">
                                        <span x-html="drawerContent?.old_value || 'N/A'"></span>
                                    </template>
                                    <template x-if="column === 'created'">
                                        <span>N/A</span>
                                    </template>
                                    <template x-if="['synonyms', 'cas'].includes(column)">
                                        <span x-text="drawerContent?.old_value ? drawerContent.old_value.join(', ') : 'N/A'"></span>
                                    </template>
                                    <template x-if="['organisms', 'sampleLocations', 'citations'].includes(column)">
                                        <span x-text="drawerContent?.old_value ? `Detached from: ${drawerContent.old_value}` : 'N/A'"></span>
                                    </template>
                                    <template x-if="!['2d', '3d', 'iupac_name', 'created', 'synonyms', 'cas', 'organisms', 'sampleLocations', 'citations'].includes(column)">
                                        <span x-text="drawerContent?.old_value || 'N/A'"></span>
                                    </template>
                                </td>

                                <!-- New Value -->
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    <template x-if="['2d', '3d'].includes(column)">
                                        <pre class="whitespace-pre-wrap text-xs overflow-auto max-h-40" x-text="drawerContent?.new_value || 'N/A'"></pre>
                                    </template>
                                    <template x-if="column === 'iupac_name'">
                                        <span x-html="drawerContent?.new_value || 'N/A'"></span>
                                    </template>
                                    <template x-if="column === 'created'">
                                        <span>Initial creation of the compound on COCONUT</span>
                                    </template>
                                    <template x-if="['synonyms', 'cas'].includes(column)">
                                        <div>
                                            <template x-if="drawerContent?.old_value && drawerContent?.new_value && Array.from(new Set(drawerContent.old_value.filter(x => !drawerContent.new_value.includes(x)))).length > 0">
                                                <div>
                                                    <span class="font-bold">Removed: </span><br>
                                                    <span x-text="Array.from(new Set(drawerContent.old_value.filter(x => !drawerContent.new_value.includes(x)))).join(', ')"></span>
                                                </div>
                                            </template>
                                            <template x-if="drawerContent?.old_value && drawerContent?.new_value && Array.from(new Set(drawerContent.new_value.filter(x => !drawerContent.old_value.includes(x)))).length > 0">
                                                <div>
                                                    <span class="font-bold">Added: </span><br>
                                                    <span x-text="Array.from(new Set(drawerContent.new_value.filter(x => !drawerContent.old_value.includes(x)))).join(', ')"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="['organisms', 'sampleLocations', 'citations'].includes(column)">
                                        <span x-text="drawerContent?.new_value ? `Attached to: ${drawerContent.new_value}` : 'N/A'"></span>
                                    </template>
                                    <template x-if="!['2d', '3d', 'iupac_name', 'created', 'synonyms', 'cas', 'organisms', 'sampleLocations', 'citations'].includes(column)">
                                        <span x-text="drawerContent?.new_value || 'N/A'"></span>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>