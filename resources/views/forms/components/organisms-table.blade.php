@php
    $pageComponent = $getLivewire();
@endphp
<div 
    x-data="{ 
        targetId: null, 
        targetName: null,
        openMerge(id, name) {
            this.targetId = id;
            this.targetName = name;
            $dispatch('open-modal', { id: 'merge-organism-modal' });
        },
        doMerge() {
            $dispatch('close-modal', { id: 'merge-organism-modal' });
            @this.call('mergeOrganism', this.targetId);
        }
    }"
    class="space-y-2" 
    style="overflow: visible;"
>
    @forelse($getTableData($getRecord()->name) as $row)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm" style="padding: 14px 24px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; padding-right: 20px;">
                    <h3 class="text-lg font-bold italic text-gray-900 dark:text-gray-100" style="margin: 0 0 6px 0;">
                        {{ $row['name'] }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400" style="margin: 0 0 4px 0;">
                        Molecules - {{ number_format($row['molecule_count']) }}
                    </p>
                    @if($row['iri'])
                    <a href="{{ $row['iri'] }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline" style="word-break: break-all;">
                        IRI - {{ $row['iri'] }}
                    </a>
                    @endif
                </td>
                <td style="vertical-align: middle; text-align: right; white-space: nowrap;">
                    <div class="flex gap-2 justify-end">
                        <x-filament::button
                            :href="route('filament.dashboard.resources.organisms.view', $row['id'])"
                            tag="a"
                            target="_blank"
                            color="gray"
                            size="sm"
                        >
                            View
                        </x-filament::button>
                        
                        <x-filament::button
                            color="danger"
                            size="sm"
                            x-on:click="openMerge({{ $row['id'] }}, '{{ addslashes($row['name']) }}')"
                        >
                            Merge
                        </x-filament::button>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @empty
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <x-heroicon-o-magnifying-glass class="mx-auto h-8 w-8 mb-2" />
        <p class="text-sm font-medium">No similar organisms found</p>
        <p class="text-xs">No organisms with matching genus in the database</p>
    </div>
    @endforelse

    <!-- Merge Modal -->
    <x-filament::modal id="merge-organism-modal" width="md">
        <x-slot name="heading">
            <div class="flex items-center gap-2 text-danger-600">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                Merge Organism
            </div>
        </x-slot>

        <div class="space-y-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">From:</span>
                        <span class="font-medium">{{ $getRecord()->name }}</span>
                    </div>
                    <div class="flex justify-center">
                        <x-heroicon-o-arrow-down class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Into:</span>
                        <span class="font-medium" x-text="targetName"></span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg text-sm">
                <p class="font-medium text-amber-800 dark:text-amber-200 mb-1">Will transfer:</p>
                <ul class="text-amber-700 dark:text-amber-300 space-y-1">
                    <li>• {{ number_format($getRecord()->molecule_count) }} molecules</li>
                    <li>• {{ number_format($getRecord()->sampleLocations->count()) }} sample locations</li>
                </ul>
            </div>

            <p class="text-sm text-danger-600 font-medium">
                ⚠️ This action cannot be undone.
            </p>
        </div>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'merge-organism-modal' })">
                Cancel
            </x-filament::button>
            <x-filament::button color="danger" x-on:click="doMerge()">
                Yes, Merge
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
