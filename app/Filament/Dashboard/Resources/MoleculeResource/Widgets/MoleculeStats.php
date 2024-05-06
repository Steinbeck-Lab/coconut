<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Widgets;

use App\Models\Molecule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class MoleculeStats extends BaseWidget
{
    public ?Molecule $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Organisms', Cache::rememberForever('stats.molecules'.$this->record->id.'organisms.count', function () {
                return $this->record->organisms->count();
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.molecules'.$this->record->id.'geo_locations.count', function () {
                return $this->record->geoLocations->count();
            })),
        ];
    }
}
