<?php

namespace App\Livewire;

use Livewire\Component;

class CopyButton extends Component
{
    public $textToCopy;

    public function mount($textToCopy)
    {
        $this->textToCopy = $textToCopy;
    }

    public function render()
    {
        return view('livewire.copy-button');
    }
}
