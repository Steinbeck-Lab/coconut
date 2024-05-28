<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            Stat::make('Total Collections', Cache::get('stats.collections')),
            Stat::make('Total Citations', Cache::get('stats.citations')),

            Stat::make('Total Organisms', Cache::get('stats.organisms')),
            Stat::make('Total Geo Locations', Cache::get('stats.geo_locations')),
            Stat::make('Total Reports', Cache::get('stats.reports')),
        ];
    }
}
