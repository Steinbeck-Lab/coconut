<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Widgets;

use App\Models\GeoLocation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class GeoLocationStats extends BaseWidget
{
    public ?GeoLocation $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::get('stats.geo_locations'.$this->record->id.'molecules.count')),
            Stat::make('Total Organisms', Cache::get('stats.geo_locations'.$this->record->id.'organisms.count')),
        ];
    }
}
