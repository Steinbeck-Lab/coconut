<?php

namespace App\Filament\Resources\LicenseResource\Widgets;

use App\Models\License;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LicenseOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Licenses', License::count())
                ->description('Total count')
                ->color('primary'),
        ];
    }
}
