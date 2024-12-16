<div x-data="{ open: false }" x-effect="open && $wire.getEntryDetails()" class="inline">
    <!-- Trigger Button -->
    <svg @click="open = true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline cursor-pointer hover:text-gray-700">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>

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
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
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