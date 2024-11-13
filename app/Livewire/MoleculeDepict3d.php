<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class MoleculeDepict3d extends Component
{
    public $smiles = null;

    public $height = 200;

    public $width = '100%';

    public $CIP = true;

    #[Computed]
    public function source()
    {
        return env('CM_API').'depict/3D?smiles='.urlencode($this->smiles).'&height='.$this->height.'&width='.$this->width.'&CIP='.$this->CIP.'&toolkit=rdkit';
    }

    public function downloadMolFile($toolkit)
    {
        $structureData = json_decode($this->molecule->structures->getAttributes()['3d'], true);

        return response()->streamDownload(function () use ($structureData) {
            echo $structureData;
        }, $this->identifier.'.sdf', [
            'Content-Type' => 'chemical/x-mdl-sdfile',
        ]);
    }

    public function render()
    {
        return view('livewire.molecule-depict3d');
    }
}
