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
            Stat::make('Entries', $this->record->total_entries)
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', $this->record->successful_entries)
                ->description('Successful count')
                ->color('success'),
            Stat::make('Entries', $this->record->failed_entries)
                ->description('Failed entries')
                ->color('danger'),
            Stat::make('Total Molecules', $this->record->molecules_count),
            Stat::make('Total Citations', $this->record->citations_count),
            Stat::make('Total Organisms', $this->record->organisms_count),
            Stat::make('Total Geo Locations', $this->record->geo_count),
        ];
    }
}
