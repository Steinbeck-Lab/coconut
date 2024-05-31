<?php

namespace App\Livewire;

use App\Models\Molecule;
use Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MoleculeDetails extends Component
{
    public $molecule;

    public function mount($id)
    {
        $this->molecule = Cache::remember('molecules.'.$id, 1440, function () use ($id) {
            return Molecule::with('properties', 'citations', 'collections', 'audits', 'variants', 'organisms', 'geo_locations', 'related')->where('identifier', $id)->first();
        });
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.molecule-details', [
            'molecule' => $this->molecule,
        ]);
    }
}
