<?php

namespace App\Livewire;

use Livewire\Component;

class MoleculeEditor extends Component
{
    public $confirmingUserDeletion = true;

    public function render()
    {
        return view('livewire.molecule-editor');
    }
}
