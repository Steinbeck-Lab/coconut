<div class="bg-white rounded-lg hover:shadow-xl shadow border">
    @if($molecule->identifier)
        <a href="{{ route('compound', $molecule->identifier) }}" wire:navigate>
        <div class="group relative flex flex-col overflow-hidden">
            <div class="aspect-h-3 aspect-w-3 sm:aspect-none group-hover:opacity-75 h-56">
                <livewire:molecule-depict2d :smiles="$molecule->canonical_smiles">
            </div>
            <div class="flex flex-1 border-t flex-col p-4">
                 <div class="flex items-center">
                    @for ($i = 0; $i < $molecule->annotation_level; $i++)
                        <span class="text-amber-300">★<span>
                    @endfor
                    @for ($i = $molecule->annotation_level; $i < 5; $i++)
                        ☆
                    @endfor
                </div>
                <div class="flex flex-1 flex-col justify-end">
                    <p class="text-sm font-medium text-gray-500">{{ $molecule->identifier }}</p>
                </div>
                <h3 class="mt-1 text-base font-bold text-gray-900 capitalize">
                    @if($molecule->name)
                        {{ $molecule->name }}
                    @else
                        {{ $molecule->iupac_name }}
                    @endif
                </h3>
            </div>
        </div>
    </a>
    @endif
</div>
