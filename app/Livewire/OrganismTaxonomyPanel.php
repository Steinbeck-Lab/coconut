<?php

namespace App\Livewire;

use App\Models\Organism;
use App\Services\OrganismTaxonomy\OrganismTaxonomyPresenter;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OrganismTaxonomyPanel extends Component
{
    #[Locked]
    public int $organismId;

    public function mount(int $organismId): void
    {
        $this->organismId = $organismId;
    }

    public function render(OrganismTaxonomyPresenter $presenter): View
    {
        $organism = Organism::query()->find($this->organismId);

        if (! $organism) {
            return view('livewire.organism-taxonomy-panel', [
                'organism' => null,
                'taxonomy' => null,
            ]);
        }

        return view('livewire.organism-taxonomy-panel', [
            'organism' => $organism,
            'taxonomy' => $presenter->forOrganism($organism),
        ]);
    }
}
