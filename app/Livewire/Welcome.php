<?php

namespace App\Livewire;

use Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Welcome extends Component
{
    public $totalMolecules;

    public $totalCollections;

    public $uniqueOrganisms;

    public $citationsMapped;

    #[Layout('layouts.guest')]
    public function render()
    {
        // Assign values to the variables
        $this->totalMolecules = Cache::get('stats.molecules', 0);
        $this->totalCollections = Cache::get('stats.collections', 0);
        $this->uniqueOrganisms = Cache::get('stats.organisms', 0);
        $this->citationsMapped = Cache::get('stats.citations', 0);

        return view('livewire.welcome', [
            'totalMolecules' => $this->totalMolecules,
            'totalCollections' => $this->totalCollections,
            'uniqueOrganisms' => $this->uniqueOrganisms,
            'citationsMapped' => $this->citationsMapped,
        ]);
    }
}
