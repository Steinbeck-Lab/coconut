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
            class="text-base font-semibold text-text-dark hover:text-slate-600"
            wire:transition="fade">
            <span x-show="!on">View complete history →</span>
            <span x-show="on">View complete history ↓</span>
        </button>

        <div x-show="on" class="pl-1 bg-white px-4 pb-7 shadow sm:rounded-lg border">
            <div class="lg:col-start-3">
                <!-- Activity feed -->
                <ul role="list" class="mt-6 space-y-3">
                    @foreach ($audit_data as $audit)
                    @if (array_key_exists('affected_columns', $audit))
                    <li class="relative flex gap-x-1">
                        <div class="absolute -bottom-6 left-0 top-0 flex w-6 justify-center">
                            <div class="w-px bg-gray-200"></div>
                        </div>
                        <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                            <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                        </div>
                        <div class="flex-auto">
                            <p class="text-xs text-gray-900">
                                <span>{{ $audit['user_name'] ?? 'COCONUT Curator' }}</span>
                                <span class="ml-1 font-bold border px-2 py-1 bg-white inline-flex rounded-md shadow-sm mb-2 capitalize mt-1">
                                    {{ $audit['event'] ?? '' }}&nbsp;
                                    <span>{{ \Carbon\Carbon::parse($audit['created_at'])->diffForHumans() }}</span>
                                </span>
                            </p>

                            @foreach ($audit['affected_columns'] as $column_name => $column_values)
                            <div class="flex justify-between gap-x-4">
                                <div class="py-0.5 text-xs leading-5 text-gray-500">
                                    <div @click="
                                                    drawerOpen = true; 
                                                    event = '{{ $audit['event'] }}';
                                                    column = '{{ $column_name }}';
                                                    drawerContent = {{ json_encode($column_values, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                                        class="hover:cursor-pointer hover:text-blue-500 font-medium text-sm text-gray-900">
                                        {{ Str::of($column_name)->camel()->replace('_', ' ')->replaceMatches('/[A-Z]/', ' $0')->title() }}
                                    </div>
                                </div>
                            </div>
                            @endforeach
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
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="drawerOpen = false"></div>

            <!-- Drawer panel -->
            <div class="relative bg-white w-full max-w-md ml-auto shadow-xl transform transition-all">
                <div class="flex justify-between items-center p-4 border-b">
                    <h2 class="text-lg font-medium text-gray-900 capitalize" x-text="event"></h2>
                    <button @click="drawerOpen = false" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close panel</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Column</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Old Value</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">New Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
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