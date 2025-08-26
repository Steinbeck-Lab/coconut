<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Database Status', 'Operational')
                ->description('System health')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Cache Status', 'Active')
                ->description('Data cached for performance')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('primary'),

            Stat::make('Last Updated', now()->format('M d, Y'))
                ->description('Data freshness')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
