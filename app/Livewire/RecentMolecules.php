<?php

namespace App\Livewire;

use App\Http\Resources\MoleculeResource;
use App\Models\Molecule;
use Livewire\Component;
use Livewire\WithPagination;

class RecentMolecules extends Component
{
    use WithPagination;

    public $size = 5;

    public function render()
    {
        return view('livewire.recent-molecules', [
            'molecules' => MoleculeResource::collection(Molecule::where('has_variants', true)->where('name', '!=', null)->orderByDesc('updated_at')->paginate($this->size)),
        ]);
    }
}
