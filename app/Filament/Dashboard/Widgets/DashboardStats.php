<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Collection;
use App\Models\Citation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Models\GeoLocation;
use App\Models\Report;
use Illuminate\Support\Facades\Cache;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Collections', Cache::rememberForever('stats.collections', function () {
                return Collection::count();
            })),
            Stat::make('Total Citations', Cache::rememberForever('stats.citations', function () {
                return Citation::count();
            })),
            Stat::make('Total Molecules', Cache::rememberForever('stats.molecules', function () {
                return Molecule::count();
            })),
            Stat::make('Total Organisms', Cache::rememberForever('stats.organisms', function () {
                return Organism::count();
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.geo_locations', function () {
                return GeoLocation::count();
            })),
            Stat::make('Total Reports', Cache::rememberForever('stats.reports', function () {
                return Report::count();
            })),
        ];
    }
}
