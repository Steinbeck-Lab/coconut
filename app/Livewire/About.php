<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

class About extends Component
{
    public $terms = '';

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.about');
    }
}
