<div class="overflow-x-auto">
    @foreach($getTableData($getRecord()->name) as $row)
    <div class="border p-2 px-4 rounded-md mb-2 flex items-center justify-between">
        <div class="mr-4">
            <p class="text-md font-bold">
                {{ $row['name'] }} 
            </p>
            <p class="text-sm">
                Molecules - {{ $row['molecule_count'] }} 
            </p>
            <p class="text-sm">
                <a href="{{$row['iri']}}" target="_blank" class="text-blue-600 hover:underline">
                    IRI - {{ $row['iri'] }}
                </a>
            </p>
        </div>
        <x-button href="{{config('app.url').'/dashboard/organisms/'.$row['id'].'/edit'}}" target="_blank" wire:navigate.hover>
            Edit
        </x-button>
    </div>
    @endforeach
</div>