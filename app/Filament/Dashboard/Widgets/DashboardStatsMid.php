<?php

namespace App\Filament\Dashboard\Widgets;

use App\Models\Molecule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DashboardStatsMid extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Molecules', Cache::rememberForever('stats.molecules', function () {
                return Molecule::count();
            })),
            Stat::make('Total Non-Stereo Molecules', Cache::rememberForever('stats.molecules.non_stereo', function () {
                return Molecule::where([
                    ['has_stereo', false],
                    ['is_parent', false],
                ])->count();
            })),
            Stat::make('Total Stereo Molecules', Cache::rememberForever('stats.molecules.stereo', function () {
                return Molecule::where([
                    ['has_stereo', true],
                ])->count();
            }))
                ->description(
                    'Total parent molecules: '.Cache::rememberForever('stats.molecules.parent', function () {
                        return Molecule::where([
                            ['has_stereo', false],
                            ['is_parent', true],
                        ])->count();
                    })
                )
                ->color('primary'),
        ];
    }
}
