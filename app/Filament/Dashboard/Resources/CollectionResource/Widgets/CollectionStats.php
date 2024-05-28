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
            Stat::make('Entries', Cache::rememberForever('stats.collections'.$this->record->id.'count.entries', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw('collection_id='.$this->record->id)->get()[0]->count;
            }))
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', Cache::rememberForever('stats.collections'.$this->record->id.'count.passed_entries', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw("status = 'PASSED'")->get()[0]->count;
            }))
                ->description('Successful count')
                ->color('success'),
            Stat::make('Entries', Cache::rememberForever('stats.collections'.$this->record->id.'count.rejected_entries', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw("status = 'REJECTED'")->get()[0]->count;
            }))
                ->description('Failed entries')
                ->color('danger'),
            Stat::make('Total Molecules', Cache::rememberForever('stats.collections'.$this->record->id.'count.molecules', function () {
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw('collection_id ='.$this->record->id)->get()[0]->count;
            })),
            Stat::make('Total Citations', Cache::rememberForever('stats.collections'.$this->record->id.'count.citations', function () {
                return DB::table('citables')->selectRaw('count(*)')->whereRaw("citable_type='App\Models\Collection' and citable_id=".$this->record->id)->get()[0]->count;
            })),
            Stat::make('Total Organisms', Cache::rememberForever('stats.collections'.$this->record->id.'count.organisms', function () {
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw('collection_id='.$this->record->id)->Join('molecule_organism', 'collection_molecule.molecule_id', '=', 'molecule_organism.molecule_id')->get()[0]->count;
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.collections'.$this->record->id.'count.geo_locations', function () {
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw('collection_id='.$this->record->id)->Join('geo_location_molecule', 'collection_molecule.molecule_id', '=', 'geo_location_molecule.molecule_id')->get()[0]->count;
            })),
        ];
    }
}
