<?php

namespace App\Livewire;

use App\Models\Molecule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MoleculeDetails extends Component
{
    public $molecule;

    public function mount($id)
    {
        $this->molecule = Molecule::with('properties', 'citations', 'collections')->where('identifier', $id)->first();
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.molecule-details', [
            'molecule' => $this->molecule,
        ]);
    }
}
