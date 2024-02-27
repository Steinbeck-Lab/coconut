<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Terms extends Component
{
    public $terms = '';

    public function mount()
    {
        $termsFile = Jetstream::localizedMarkdownPath('terms.md');
        $this->terms = Str::markdown(file_get_contents($termsFile));
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('terms');
    }
}
