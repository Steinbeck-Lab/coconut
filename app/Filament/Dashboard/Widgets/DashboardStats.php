<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Citation;
use App\Models\Collection;
use App\Models\GeoLocation;
use App\Models\Organism;
use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 2;

    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Collections', Cache::rememberForever('stats.collections', function () {
                return Collection::count();
            })),
            Stat::make('Total Citations', Cache::rememberForever('stats.citations', function () {
                return Citation::count();
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
