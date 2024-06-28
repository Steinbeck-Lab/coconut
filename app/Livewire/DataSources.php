<?php

namespace App\Livewire;

use App\Models\Collection;
use Cache;
use Livewire\Component;

class DataSources extends Component
{
    public $collections = [];

    public function mount()
    {
        $this->collections = Cache::rememberForever('collections', function () {
            return Collection::take(10)->get(['title', 'image'])->toArray();
        });
    }

    public function render()
    {
        return view('livewire.data-sources');
    }
}
