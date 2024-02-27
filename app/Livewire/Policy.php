<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Policy extends Component
{
    public $policy = '';

    public function mount()
    {
        $policyFile = Jetstream::localizedMarkdownPath('policy.md');
        $this->policy = Str::markdown(file_get_contents($policyFile));
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('policy');
    }
}
