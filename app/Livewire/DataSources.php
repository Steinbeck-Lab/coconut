<?php

namespace App\Livewire;

use App\Models\Collection;
use Livewire\Component;

class DataSources extends Component
{
    public $collections = [];

    public function mount()
    {
        $this->collections = Collection::where('is_public', 1)->get()->pluck('title');
    }

    public function render()
    {
        return view('livewire.data-sources');
    }
}
