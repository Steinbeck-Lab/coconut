<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use App\Models\Collection;

class CollectionStats extends BaseWidget
{
    public ?Collection $record = null;
    
    protected function getStats(): array
    {
        return [
            // Stat::make('Entries', $this->record->entries->count())
            //     ->description('Total count')
            //     ->color('primary'),
            // Stat::make('Total Entries', Cache::rememberForever('stats.collections', function () {
            //     return Collection::count();
            // })),
            Stat::make('Total Entries', Cache::rememberForever('stats.collections'.$this->record->id.'entries.count', function () {
                return $this->record->entries->count();
            })),
            Stat::make('Total Molecules', Cache::rememberForever('stats.collections'.$this->record->id.'molecules.count', function () {
                return $this->record->molecules->count();
            })),
            Stat::make('Total Citations', Cache::rememberForever('stats.collections'.$this->record->id.'citations.count', function () {
                return $this->record->citations->count();
            })),
            Stat::make('Total Organisms', Cache::rememberForever('stats.collections'.$this->record->id.'organisms.count', function () {
                // refactor the below with eloquent relations if possible
                $molecules = $this->record->molecules;
                $count = 0;
                foreach($molecules as $molecule) {
                    $count += $molecule->organisms()->count();
                }
                return $count;
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.collections'.$this->record->id.'geo_locations.count', function () {
                // refactor the below with eloquent relations if possible
                $molecules = $this->record->molecules;
                $count = 0;
                foreach($molecules as $molecule) {
                    $count += $molecule->geoLocations()->count();
                }
                return $count;
            })),
        ];
    }
}
