<div>
    @foreach($data['response']['docs'] as $row)
    <div class="border p-2 px-4 rounded-md mb-2 flex items-center justify-between">
        <div class="mr-4">
            <p class="text-md font-bold">
                {{ $row['label'] }} (Rank: {{ $row['type'] }})
            </p>
            @if ($row['description'])
            <p class="text-md font-bold">
                {{ $row['description'] }} 
            </p>
            @endif
            <p>
                <a href="{{$row['iri']}}" target="_blank" class="text-blue-600 hover:underline">
                    IRI - {{ $row['iri'] }}
                </a>
            </p>
        </div>
        <x-button wire:click="selectRow({{ $loop->index }})">
            Select
        </x-button>
    </div>
    @endforeach
</div>