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
            Stat::make('Total Collections', Cache::rememberForever('stats.collections', function () {
                return DB::table('collections')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Citations', Cache::rememberForever('stats.citations', function () {
                return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
            })),

            Stat::make('Total Organisms', Cache::rememberForever('stats.organisms', function () {
                return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Geo Locations', Cache::rememberForever('stats.geo_locations', function () {
                return DB::table('geo_locations')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Reports', Cache::rememberForever('stats.reports', function () {
                return DB::table('reports')->selectRaw('count(*)')->get()[0]->count;
            })),
        ];
    }
}
