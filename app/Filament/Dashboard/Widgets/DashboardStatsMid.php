<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsMid extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::rememberForever('stats.molecules', function () {
                return DB::table('molecules')->selectRaw('count(*)')->get()[0]->count;
            })),
            Stat::make('Total Non-Stereo Molecules', Cache::rememberForever('stats.molecules.non_stereo', function () {
                return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=false and is_parent=false')->get()[0]->count;
            })),
            Stat::make('Total Stereo Molecules', Cache::rememberForever('stats.molecules.stereo', function () {
                return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=true')->get()[0]->count;
            }))
                ->description(
                    'Total parent molecules: '.Cache::rememberForever('stats.molecules.parent', function () {
                        return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=false and is_parent=true')->get()[0]->count;
                    })
                )
                ->color('primary'),
        ];
    }
}
