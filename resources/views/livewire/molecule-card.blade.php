<div class="bg-white rounded-lg hover:shadow-xl shadow border">
    @if($molecule->identifier)
        <a href="{{ route('compound', $molecule->identifier) }}" wire:navigate>
        <div class="group relative flex flex-col overflow-hidden">
            <div class="aspect-h-3 aspect-w-3 sm:aspect-none group-hover:opacity-75 h-56">
                <livewire:molecule-depict2d :smiles="$molecule->canonical_smiles">
            </div>
            <div class="flex flex-1 border-t flex-col space-y-2 p-4">
                <div class="flex flex-1 flex-col justify-end">
                    <p class="text-base font-medium text-gray-500">{{ $molecule->identifier }}</p>
                </div>
                <h3 class="text-sm font-bold text-gray-900">
                    <span aria-hidden="true" class="absolute inset-0"></span>
                    {{ $molecule->iupac_name }}
                </h3>
            </div>
        </div>
    </a>
    @endif
</div>
