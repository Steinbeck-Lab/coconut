<?php

namespace App\Livewire;

use Livewire\Component;

class StructureEditor extends Component
{
    public $isOpen = false;

    public $smiles = '';

    protected $listeners = ['openModal'];

    public function openModal($smiles = '')
    {
        $this->smiles = $smiles;
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function render()
    {
        return view('livewire.structure-editor');
    }
}
