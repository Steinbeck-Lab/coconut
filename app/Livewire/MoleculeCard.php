<?php

namespace App\Livewire;

use Livewire\Component;

class MoleculeCard extends Component
{
    public $molecule = null;

    public function mount()
    {
        $this->molecule = json_decode($this->molecule);
    }

    public function render()
    {
        return view('livewire.molecule-card');
    }
}
