<?php

namespace App\Livewire;

use App\Models\Collection;
use Livewire\Component;

class CollectionVersionHistory extends Component
{
    public int $lineageRootId;

    public ?int $selectedVersion = null;

    public function mount(int $lineageRootId, ?int $selectedVersion = null): void
    {
        $this->lineageRootId = $lineageRootId;
        $this->selectedVersion = $selectedVersion;
    }

    public function selectVersion(int $version): void
    {
        $this->selectedVersion = $version;
    }

    public function render()
    {
        $root = Collection::query()->findOrFail($this->lineageRootId);
        $versions = $root->lineageVersionsQuery()->get();
        $selected = $this->selectedVersion
            ? $versions->firstWhere('version', $this->selectedVersion)
            : $versions->firstWhere('is_latest', true);

        return view('livewire.collection-version-history', [
            'versions' => $versions,
            'selected' => $selected,
            'baseDoi' => $root->doi_base,
        ]);
    }
}
