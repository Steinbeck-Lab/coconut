<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Widgets;

use App\Models\GeoLocation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GeoLocationStats extends BaseWidget
{
    public ?GeoLocation $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::flexible('stats.geo_locations'.$this->record->id.'molecules.count', [172800, 259200], function () {
                return DB::table('geo_location_molecule')
                    ->selectRaw('count(*)')
                    ->where('geo_location_molecule.geo_location_id', '=', $this->record->id)
                    ->get()[0]->count;
            })),
        ];
    }
}
