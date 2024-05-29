<div class="bg-white rounded-lg hover:shadow-xl shadow border">
    @if($molecule->identifier)
        <a href="{{ route('compound', $molecule->identifier) }}" wire:navigate>
        <div class="group relative flex flex-col overflow-hidden">
            <div class="aspect-h-3 aspect-w-3 sm:aspect-none group-hover:opacity-75 h-56">
                <livewire:molecule-depict2d :smiles="$molecule->canonical_smiles">
            </div>
            <div class="flex flex-1 border-t flex-col space-y-2 p-4">
                 <div class="flex items-center">
                    @foreach (range(1, $molecule->annotation_level) as $i) <svg :key="{{ $i }}" class="inline text-yellow-400 h-5 w-5 flex-shrink-0" x-state:on="Active" x-state:off="Inactive" x-state-description='Active: "text-yellow-400", Inactive: "text-gray-200"' x-description="Heroicon name: mini/star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd"></path></svg> @endforeach
                </div>
                <div class="flex flex-1 flex-col justify-end">
                    <p class="text-base font-medium text-gray-500">{{ $molecule->identifier }}</p>
                </div>
                <h3 class="text-sm font-bold text-gray-900 capitalize">
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
