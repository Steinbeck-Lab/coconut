<div x-data="{ open: false }" x-effect="open && $wire.getEntryDetails()" class="inline">

    <button @click="open = true" type="button" class="inline-flex items-center gap-x-1.5 rounded-l-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 ring-inset hover:bg-gray-50">
        <svg class="-ml-0.5 size-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M10 2c-1.716 0-3.408.106-5.07.31C3.806 2.45 3 3.414 3 4.517V17.25a.75.75 0 0 0 1.075.676L10 15.082l5.925 2.844A.75.75 0 0 0 17 17.25V4.517c0-1.103-.806-2.068-1.93-2.207A41.403 41.403 0 0 0 10 2Z" clip-rule="evenodd"></path>
        </svg>
        Reference ID: {{ $reference }}
    </button>

    <!-- Modal Backdrop -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 z-40"
        @click="open = false">
    </div>

    <!-- Modal Content -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.away="open = false">

        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">Entry Details</h3>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-0">
                    @foreach ($entry_details as $entry)
                    <table class="w-full mb-4">
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td colspan="2" class="px-4 py-2 text-sm text-center text-gray-500">
                                    <livewire:molecule-depict2d :height="300" :molecule="$mol" :smiles="$entry->canonical_smiles"
                                        :name="$mol->name" :identifier="$mol->identifier" :options="false" lazy="on-load" />
                                    {{$entry->canonical_smiles ?? '-'}}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Name</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->name ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">DOI</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->doi ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Organism</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->organism ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Organism Part</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->organism_part ?? '-'}}</td>
                            </tr>

                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Geo Location</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->geo_location ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Location</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->location ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Molecular Formula</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->molecular_formula ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">Created at</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{$entry->created_at ?? '-'}}</td>
                            </tr>
                        </tbody>
                    </table>
                    @endforeach
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end p-4 border-t">
                    <button @click="open = false" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>