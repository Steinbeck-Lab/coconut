<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Widgets;

use App\Models\Collection;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollectionStats extends BaseWidget
{
    public ?Collection $record = null;

    protected function getStats(): array
    {
        return [
            // Commented is the model query that we can use in case we decide not to use app level caching as the app scales up.

            // Stat::make('Entries', Cache::rememberForever('stats.collections'.$this->record->id.'entries.count', function () {
            //     return DB::table('entries')->selectRaw('count(*)')->whereRaw('collection_id='.$this->record->id)->get()[0]->count;
            // }))
            //     ->description('Total count')
            //     ->color('primary'),
            Stat::make('Entries', Cache::get('stats.collections'.$this->record->id.'entries.count'))
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', Cache::get('stats.collections'.$this->record->id.'passed_entries.count'))
                ->description('Successful count')
                ->color('success'),
            Stat::make('Entries', Cache::get('stats.collections'.$this->record->id.'rejected_entries.count'))
                ->description('Failed entries')
                ->color('danger'),
            // Stat::make('Total Entries',  $this->record->entries->count()),
            Stat::make('Total Molecules', $this->record->molecules->count()),
            Stat::make('Total Citations', $this->record->citations->count()),
            // Stat::make('Total Organisms', Cache::rememberForever('stats.collections'.$this->record->id.'organisms.count', function () {
            //     // refactor the below with eloquent relations if possible
            //     $molecules = $this->record->molecules;
            //     $count = 0;
            //     foreach ($molecules as $molecule) {
            //         $count += $molecule->organisms()->count();
            //     }
            //  ];
            // })),
            Stat::make('Geo Locations', Cache::rememberForever('stats.collections'.$this->record->id.'geo_locations.count', function () {
                // refactor the below with eloquent relations if possible
                $molecules = $this->record->molecules;
                $count = 0;
                foreach ($molecules as $molecule) {
                    $count += $molecule->geo_locations()->count();
                }

                return $count;
            })),
        ];
    }
}
