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
        // Main statistics matching the stats page
        $totalMolecules = Cache::flexible('stats.molecules', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('active=true and NOT (is_parent=true AND has_variants=true)')->get()[0]->count;
        });

        $totalCollections = Cache::flexible('stats.collections', [172800, 259200], function () {
            return DB::table('collections')->selectRaw('count(*)')->whereRaw("status = 'PUBLISHED'")->get()[0]->count;
        });

        $uniqueOrganisms = Cache::flexible('stats.organisms', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
        });

        $citationsMapped = Cache::flexible('stats.citations', [172800, 259200], function () {
            return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
        });

        return [
            // Main Statistics (Primary Row)
            Stat::make('Unique Molecules', number_format($totalMolecules))
                ->description('Active molecules in database')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('primary'),

            Stat::make('Total Collections', number_format($totalCollections))
                ->description('Published collections')
                ->descriptionIcon('heroicon-m-folder')
                ->color('success'),

            Stat::make('Unique Organisms', number_format($uniqueOrganisms))
                ->description('Organisms in database')
                ->descriptionIcon('heroicon-m-globe-europe-africa')
                ->color('info'),

            Stat::make('Citations Mapped', number_format($citationsMapped))
                ->description('Literature references')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('warning'),
        ];
    }
}
