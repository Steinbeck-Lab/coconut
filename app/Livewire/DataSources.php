<?php

namespace App\Livewire;

use App\Models\Collection;
use Livewire\Component;
use Cache;

class DataSources extends Component
{
    public $collections = [];

    public function mount()
    {
        $this->collections = Cache::rememberForever('collections', function (){
            return Collection::limit(10)->pluck('title');
        });
    }

    public function render()
    {
        return view('livewire.data-sources');
    }
}
