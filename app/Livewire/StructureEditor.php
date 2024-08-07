<?php

namespace App\Livewire;

use Livewire\Component;

class StructureEditor extends Component
{
    public $mode = 'inline';

    public function render()
    {
        return view('livewire.structure-editor');
    }
}
