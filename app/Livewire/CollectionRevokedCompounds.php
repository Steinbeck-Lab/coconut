<?php

namespace App\Livewire;

use App\Models\CollectionVersionRevocation;
use Livewire\Component;

class CollectionRevokedCompounds extends Component
{
    public int $lineageRootId;

    public bool $expanded = false;

    public ?int $filterVersion = null;

    public function mount(int $lineageRootId, ?int $filterVersion = null): void
    {
        $this->lineageRootId = $lineageRootId;
        $this->filterVersion = $filterVersion;
    }

    public function toggle(): void
    {
        $this->expanded = ! $this->expanded;
    }

    public function render()
    {
        $query = CollectionVersionRevocation::query()
            ->where('lineage_root_id', $this->lineageRootId)
            ->with(['molecule', 'fromCollection'])
            ->orderByDesc('revoked_at');

        if ($this->filterVersion) {
            $query->whereHas('fromCollection', fn ($q) => $q->where('version', $this->filterVersion));
        }

        $revocations = $query->get();

        return view('livewire.collection-revoked-compounds', [
            'revocations' => $revocations,
            'count' => $revocations->count(),
        ]);
    }
}
