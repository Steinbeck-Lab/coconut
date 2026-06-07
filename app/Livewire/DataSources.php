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
        $this->collections = Cache::flexible('collections', [172800, 259200], function () {
            return Collection::query()
                ->where('promote', true)
                ->where('is_latest', true)
                ->orderBy('sort_order')
                ->take(10)
                ->get(['title', 'image'])
                ->toArray();
        });
    }
}
