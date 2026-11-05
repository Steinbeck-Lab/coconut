<?php

namespace App\Livewire;

use App\Services\GeoLocationGlobeStatsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class GeoGlobe extends Component
{
    public array $countryStats = [];

    public array $totals = [];

    public function mount(GeoLocationGlobeStatsService $statsService): void
    {
        $data = $statsService->getCountryStats();

        $this->countryStats = $data['countries'];
        $this->totals = $data['totals'];
    }

    public function render()
    {
        return view('livewire.geo-globe');
    }
}
