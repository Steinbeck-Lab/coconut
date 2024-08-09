<?php

namespace App\Livewire;

use Livewire\Component;

class StructureEditor extends Component
{
    public $mode = 'inline';

    public $smiles;

    public $type = 'substructure';

    public function mount($smiles)
    {
        $this->smiles = $smiles;
    }

    public function render()
    {
        return view('livewire.structure-editor');
    }
}
