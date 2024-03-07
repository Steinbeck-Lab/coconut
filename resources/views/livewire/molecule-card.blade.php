<div>
    <a href="{{
    route("compound", $molecule->identifier)
}}" wire:navigate>
        <div class="max-w-sm rounded overflow-hidden shadow-lg">
            <livewire:molecule-depict2d :smiles="$molecule->canonical_smiles">
                <div class="px-6 py-4 border-t">
                    <div class="font-bold text-md mb-2">{{ $molecule->identifier }}</div>
                    <p class="text-gray-700 text-base truncate">
                        {{ $molecule->canonical_smiles }}
                    </p>
                </div>
        </div>
    </a>
</div>
