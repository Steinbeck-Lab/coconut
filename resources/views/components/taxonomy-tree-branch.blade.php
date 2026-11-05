@props(['nodes', 'depth' => 0, 'selectedNodeId' => null])

@foreach ($nodes as $node)
    @php
        $isSelected = $selectedNodeId === $node['id'];
        $isAncestor = $selectedNodeId !== null && (
            $selectedNodeId === $node['id']
            || str_starts_with($selectedNodeId, $node['id'].'/')
        );
        $showChildren = $isAncestor && ($node['children'] ?? []) !== [];
    @endphp
    <div style="padding-left: {{ $depth * 0.75 }}rem">
        <button type="button" wire:click="selectNode('{{ $node['id'] }}')"
            class="flex w-full items-center justify-between gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-gray-50
            @if($isSelected) bg-emerald-50 font-semibold text-emerald-800 @else text-gray-800 @endif">
            <span class="truncate">
                <span class="capitalize text-xs text-gray-400">{{ $node['rank'] }}</span>
                {{ $node['name'] }}
            </span>
            <span class="shrink-0 tabular-nums text-xs text-gray-500">{{ number_format($node['molecule_count']) }}</span>
        </button>

        @if ($showChildren)
            @include('components.taxonomy-tree-branch', [
                'nodes' => $node['children'],
                'depth' => $depth + 1,
                'selectedNodeId' => $selectedNodeId,
            ])
        @endif
    </div>
@endforeach
