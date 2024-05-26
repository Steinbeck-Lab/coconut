<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Widgets;

use App\Models\Organism;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrganismStats extends BaseWidget
{
    public ?Organism $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::rememberForever('stats.organisms'.$this->record->id.'molecules.count', function () {
                return DB::table('molecule_organism')->selectRaw('count(*)')->whereRaw('organism_id='.$this->record->id)->get()[0]->count;
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.organisms'.$this->record->id.'geo_locations.count', function () {
                // refactor the below with eloquent relations if possible
                return DB::table('molecule_organism')->selectRaw('count(*)')->whereRaw('organism_id='.$this->record->id)->Join('geo_location_molecule', 'molecule_organism.molecule_id', '=', 'geo_location_molecule.molecule_id')->get()[0]->count;
                // $molecules = $this->record->molecules;

                // $count = 0;
                // foreach ($molecules as $molecule) {
                //     $count += $molecule->geoLocations()->count();
                // }

                // return $count;
            })),
        ];
    }
}
