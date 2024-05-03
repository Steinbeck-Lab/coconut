<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use App\Models\GeoLocation;

class GeoLocationStats extends BaseWidget
{
    public ?GeoLocation $record = null;
 
    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::rememberForever('stats.geo_locations'.$this->record->id.'molecules.count', function () {
                return $this->record->molecules->count();
            })),
            Stat::make('Total Organisms', Cache::rememberForever('stats.geo_locations'.$this->record->id.'organisms.count', function () {
                // refactor the below with eloquent relations if possible
                $molecules = $this->record->molecules;
                $count = 0;
                foreach($molecules as $molecule) {
                    $count += $molecule->organisms()->count();
                }
                return $count;
            })),
        ];
    }
}
