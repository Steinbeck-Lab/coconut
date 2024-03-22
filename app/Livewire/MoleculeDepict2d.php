<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class MoleculeDepict2d extends Component
{
    public $smiles = null;

    public $height = 200;

    public $width = 200;

    public $CIP = true;

    #[Computed]
    public function source()
    {
        return env('CM_API').'depict/2D?smiles='.urlencode($this->smiles).'&height='.$this->height.'&width='.$this->width.'&CIP='.$this->CIP.'&toolkit=rdkit';
    }

    public function render()
    {
        return view('livewire.molecule-depict2d');
    }
}