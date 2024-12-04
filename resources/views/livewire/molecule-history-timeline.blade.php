<div x-data="{ on: false, event: '', column : '', drawerOpen: false, drawerContent: '' }" x-effect="on && $wire.getHistory()" class="mt-5">
    <button type="button" x-on:click="on = !on" class="text-base font-semibold text-text-dark hover:text-slate-600" wire:transition="fade">
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
                                <div x-on:click="drawerOpen = true; event= '{{$audit['event']}}' ; column = '{{ $column_name}}';drawerContent = `{{ json_encode($column_values) }}`" class="hover:cursor-pointer hover:text-blue-500 font-medium text-sm text-gray-900">
                                    {{ Str::of($column_name)->camel()->replace('_', ' ')->replaceMatches('/[A-Z]/', ' $0')->title() }}
                                </div>
                                {{-- <div class="tooltip max-w">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24">
                                        <path d="M12 2C6.488 2 2 6.489 2 12s4.488 10 10 10 10-4.489 10-10S17.512 2 12 2zM12 4c4.43 0 8 3.57 8 8s-3.57 8-8 8-8-3.57-8-8 3.57-8 8-8zm-1 3v2h2V7h-2zm0 4v6h2v-6h-2z"></path>
                                    </svg>
                                    <span class="tooltiptext ">
                                        @switch(explode('.',$column_name)[0])
                                        @case('comment')
                                            {{$column_values['new_value'][0]['comment'] ?? 'N/A'}}
                                        @break
                                        @case('active')
                                            @if ($column_values['new_value'])
                                            Activated
                                            @else
                                            Deactivated
                                            @endif
                                        @break
                                        @case('created')
                                            Initial creation of the compound on COCONUT
                                        @break
                                        @case('organisms')
                                        @case('sampleLocations')
                                            @if ($column_values['old_value'])
                                                <span class="font-bold">Detached from:</span> <br /> {{$column_values['old_value']?:'N/A'}} <br />
                                            @endif
                                            @if ($column_values['new_value'])
                                                <span class="font-bold">Attached to:</span> <br /> {{$column_values['new_value']?:'N/A'}} <br />
                                            @endif
                                        @break
                                        @case('synonyms')
                                            @if (array_diff($column_values['old_value'], $column_values['new_value']))
                                                <span class="font-bold">Removed: </span> <br /> {{implode(', ',array_diff($column_values['old_value'], $column_values['new_value']))}} <br />
                                            @endif
                                            @if (array_diff($column_values['new_value'], $column_values['old_value']))
                                                <span class="font-bold">Added: </span> <br /> {{implode(', ',array_diff($column_values['new_value'], $column_values['old_value']))}} <br />
                                            @endif
                                        @break
                                        @case('cas')
                                            @if (array_diff($column_values['old_value'], $column_values['new_value']))
                                                <span class="font-bold">Removed: </span> <br /> {{implode(', ',array_diff($column_values['old_value'], $column_values['new_value']))}} <br />
                                            @endif
                                            @if (array_diff($column_values['new_value'], $column_values['old_value']))
                                                <span class="font-bold">Added: </span> <br /> {{implode(', ',array_diff($column_values['new_value'], $column_values['old_value']))}} <br />
                                            @endif
                                        @break
                                        @case('citations')
                                            @if ($column_values['old_value'])
                                                <span class="font-bold">Detached from:</span> <br /> {{$column_values['old_value']?:'N/A'}} <br />
                                            @endif
                                            @if ($column_values['new_value'])
                                                <span class="font-bold">Attached to:</span> <br /> {{$column_values['new_value']?:'N/A'}} <br />
                                            @endif
                                        @break
                                        @default
                                            Old Value: <br /> {{$column_values['old_value']??'N/A'}} <br />
                                            New Value: <br /> {{$column_values['new_value']??'N/A'}}
                                        @endswitch
                                    </span>
                                </div> --}}
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
    <div x-show="drawerOpen" class="fixed inset-0 z-50 flex">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" x-on:click="drawerOpen = false"></div>
        <!-- Drawer panel -->
        <div class="relative bg-white w-full max-w-md ml-auto shadow-xl transform transition-all">
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-medium text-gray-900 capitalize" x-text='event'></h2>
                <button x-on:click="drawerOpen = false" class="text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <span class="sr-only">Close panel</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div>
                <template x-if="drawerContent">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column Name</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old Value</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(value, key) in [JSON.parse(drawerContent)]">
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900" x-text="column"></td>
                                    <td class="px-4 py-2 text-sm text-gray-500" x-text="value.old_value !== null ? value.old_value : 'N/A'"></td>
                                    <td class="px-4 py-2 text-sm text-gray-500" x-text="value.new_value !== null ? value.new_value : 'N/A'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </div>
        </div>
    </div>
</div>
