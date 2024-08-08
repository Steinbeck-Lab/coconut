<?php

namespace App\Livewire;

use App\Models\Molecule;
use Cache;
use Livewire\Component;
use Livewire\WithPagination;

class RecentMolecules extends Component
{
    use WithPagination;

    public $size = 5;

    public function render()
    {
        return view('livewire.recent-molecules', [
            'molecules' => Cache::rememberForever('molecules.recent', function () {
                return Molecule::where('is_parent', false)->where('active', true)->where('name', '!=', null)->where('annotation_level', '>=', 4)->orderByDesc('updated_at')->paginate($this->size);
            }),
        ]);
    }
}
