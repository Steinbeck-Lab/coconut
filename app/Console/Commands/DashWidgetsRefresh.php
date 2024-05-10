<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\Citation;
use App\Models\Collection;
use App\Models\GeoLocation;
use App\Models\Organism;
use App\Models\Report;
use App\Models\Molecule;

class DashWidgetsRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dash-widgets-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This refreshes the dashboard widgets.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Clear the cache for all dashboard widgets
        Cache::forget('stats.collections');
        Cache::forget('stats.citations');
        Cache::forget('stats.organisms');
        Cache::forget('stats.geo_locations');
        Cache::forget('stats.reports');
        Cache::forget('stats.molecules');
        Cache::forget('stats.molecules.non_stereo');
        Cache::forget('stats.molecules.stereo');
        Cache::forget('stats.molecules.parent');

        // Create the cache for all DashboardStats widgets
        Cache::rememberForever('stats.collections', function () {
            return Collection::count();
        });
        Cache::rememberForever('stats.citations', function () {
            return Citation::count();
        });
        Cache::rememberForever('stats.organisms', function () {
            return Organism::count();
        });
        Cache::rememberForever('stats.geo_locations', function () {
            return GeoLocation::count();
        });
        Cache::rememberForever('stats.reports', function () {
            return Report::count();
        });

        // Create the cache for all DashboardStatsMid widgets
        Cache::rememberForever('stats.molecules', function () {
            return Molecule::count();
        });
        Cache::rememberForever('stats.molecules.non_stereo', function () {
            return Molecule::where([
                ['has_stereo', false],
                ['is_parent', false],
            ])->count();
        });
        Cache::rememberForever('stats.molecules.stereo', function () {
            return Molecule::where([
                ['has_stereo', true],
            ])->count();
        });
        Cache::rememberForever('stats.molecules.parent', function () {
            return Molecule::where([
                ['has_stereo', false],
                ['is_parent', true],
            ])->count();
        });
    }
}
