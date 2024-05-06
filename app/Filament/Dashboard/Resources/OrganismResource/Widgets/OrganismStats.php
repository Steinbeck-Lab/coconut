<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Widgets;

use App\Models\Organism;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OrganismStats extends BaseWidget
{
    public ?Organism $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::rememberForever('stats.organisms'.$this->record->id.'molecules.count', function () {
                return $this->record->molecules->count();
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.organisms'.$this->record->id.'geo_locations.count', function () {
                // refactor the below with eloquent relations if possible
                $molecules = $this->record->molecules;
                $count = 0;
                foreach ($molecules as $molecule) {
                    $count += $molecule->geoLocations()->count();
                }

                return $count;
            })),
        ];
    }
}
