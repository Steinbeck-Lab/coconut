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
            Stat::make('Total Molecules', Cache::get('stats.collections'.$this->record->id.'molecules.count')),
            Stat::make('Total Citations', Cache::get('stats.collections'.$this->record->id.'citations.count')),
            Stat::make('Total Organisms', Cache::get('stats.collections'.$this->record->id.'organisms.count')),
            Stat::make('Total Geo Locations', Cache::get('stats.collections'.$this->record->id.'geo_locations.count')),
        ];
    }
}
