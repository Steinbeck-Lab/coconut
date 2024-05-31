<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DashboardStatsMid extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::get('stats.molecules')),
            Stat::make('Total Non-Stereo Molecules', Cache::get('stats.molecules.non_stereo')),
            Stat::make('Total Stereo Molecules', Cache::get('stats.molecules.stereo'))
                ->description(
                    'Total parent molecules: '.Cache::get('stats.molecules.parent')
                )
                ->color('primary'),
        ];
    }
}
