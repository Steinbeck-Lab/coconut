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
        $content = file_get_contents($termsFile);

        // Replace the hardcoded year with current year
        $content = preg_replace('/Copyright \(c\) \d{4}/', 'Copyright (c) '.date('Y'), $content);

        $this->terms = Str::markdown($content);
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('terms');
    }
}
