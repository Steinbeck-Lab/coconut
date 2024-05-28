<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Widgets;

use App\Models\Collection;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CollectionStats extends BaseWidget
{
    public ?Collection $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Entries', $this->record->entries->count())
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', $this->record->entries->where('status', 'PASSED')->count())
                ->description('Successful count')
                ->color('success'),
            Stat::make('Failed Entries', $this->record->entries->where('status', 'REJECTED')->count())
                ->description('Failed entries')
                ->color('danger'),
            // Stat::make('Total Entries',  $this->record->entries->count()),
            Stat::make('Molecular Entries', $this->record->molecules->count())
                ->description('(Non stereo/CNP Parent/Stereo variants)'),
            Stat::make('Citations', $this->record->citations->count()),
            Stat::make('Organisms', Cache::rememberForever('stats.collections'.$this->record->id.'organisms.count', function () {
                // refactor the below with eloquent relations if possible
                $molecules = $this->record->molecules;
                $count = 0;
                foreach ($molecules as $molecule) {
                    $count += $molecule->organisms()->count();
                }

                return $count;
            })),
            Stat::make('Geo Locations', Cache::rememberForever('stats.collections'.$this->record->id.'geo_locations.count', function () {
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
