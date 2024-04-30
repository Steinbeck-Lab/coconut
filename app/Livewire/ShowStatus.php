<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;

class ShowStatus extends Component
{
    public $status = null;

    public function mount($status)
    {
        $this->status = $status;
    }

    public function render()
    {
        return view('livewire.show-status');
    }
}
