<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 2;

    public function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Collections', Cache::flexible('stats.collections', [172800, 259200], function () {
                return DB::table('collections')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Citations', Cache::flexible('stats.citations', [172800, 259200], function () {
                return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
            })),

            Stat::make('Total Organisms', Cache::flexible('stats.organisms', [172800, 259200], function () {
                return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Geo Locations', Cache::flexible('stats.geo_locations', [172800, 259200], function () {
                return DB::table('geo_locations')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Reports', Cache::flexible('stats.reports', [172800, 259200], function () {
                return DB::table('reports')->selectRaw('count(*)')->get()[0]->count;
            })),
        ];
    }
}
