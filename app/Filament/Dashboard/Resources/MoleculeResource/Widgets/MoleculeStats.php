<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Widgets;

use App\Models\Molecule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MoleculeStats extends BaseWidget
{
    public ?Molecule $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Organisms', Cache::flexible('stats.molecules'.$this->record->id.'organisms.count', [172800, 259200], function () {
                return DB::table('molecule_organism')->selectRaw('count(*)')->whereRaw('molecule_id=?', [$this->record->id])->get()[0]->count;
            })),
            Stat::make('Total Geo Locations', Cache::flexible('stats.molecules'.$this->record->id.'geo_locations.count', [172800, 259200], function () {
                return DB::table('geo_location_molecule')->selectRaw('count(*)')->whereRaw('molecule_id=?', [$this->record->id])->get()[0]->count;
            })),
        ];
    }
}
