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
            Stat::make('Entries', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw('collection_id='.$this->record->id)->get()[0]->count;
            })
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw("status = 'PASSED'")->get()[0]->count;
            })
                ->description('Successful count')
                ->color('success'),
            Stat::make('Entries', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw("status = 'REJECTED'")->get()[0]->count;
            })
                ->description('Failed entries')
                ->color('danger'),
            // Stat::make('Total Entries',  $this->record->entries->count()),
            Stat::make('Total Molecules', function () {
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw('collection_id ='.$this->record->id)->get()[0]->count;
            }),
            Stat::make('Total Citations', function () {
                return DB::table('citables')->selectRaw('count(*)')->whereRaw("citable_type='App\Models\Collection' and citable_id=".$this->record->id)->get()[0]->count;
            }),
            // Stat::make('Total Organisms', Cache::rememberForever('stats.collections'.$this->record->id.'organisms.count', function () {
            //     // refactor the below with eloquent relations if possible
            //     $molecules = $this->record->molecules;
            //     $count = 0;
            //     foreach ($molecules as $molecule) {
            //         $count += $molecule->organisms()->count();
            //     }

            //     return $count;
            // })),
            // Stat::make('Total Geo Locations', Cache::rememberForever('stats.collections'.$this->record->id.'geo_locations.count', function () {
            //     // refactor the below with eloquent relations if possible
            //     $molecules = $this->record->molecules;
            //     $count = 0;
            //     foreach ($molecules as $molecule) {
            //         $count += $molecule->geoLocations()->count();
            //     }

            //     return $count;
            // })),
        ];
    }
}
