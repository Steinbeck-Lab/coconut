<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MoleculeDepict2d extends Component
{
    public $smiles = null;

    public $name = null;

    public $identifier = null;

    public $height = 200;

    public $width = 200;

    public $toolkit = 'cdk';

    public $options = false;

    public $CIP = true;

    #[Computed]
    public function source()
    {
        return env('CM_API').'depict/2D?smiles='.urlencode($this->smiles).'&height='.$this->height.'&width='.$this->width;
    }

    #[Computed]
    public function preview()
    {
        return env('CM_API').'depict/2D?smiles='.urlencode($this->smiles);
    }

    public function downloadMolFile($toolkit)
    {
        $response = Http::get(env('CM_API').'convert/mol2D?smiles='.urlencode($this->smiles).'&toolkit='.$toolkit);

        return response()->streamDownload(function () use ($response) {
            echo $response->body();
        }, $this->identifier.'.mol');
    }

    public function render()
    {
        return view('livewire.molecule-depict2d');
    }
}
