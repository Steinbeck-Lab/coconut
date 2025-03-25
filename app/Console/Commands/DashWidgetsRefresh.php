<?php

namespace App\Console\Commands;

use App\Models\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashWidgetsRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:cache';

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
        // Clear the cache for all widgets
        // Cache::flush();

        // Create the cache for all DashboardStats widgets
        Cache::flexible('stats.collections', [172800, 259200], function () {
            return DB::table('collections')
                ->selectRaw('count(*) as count')
                ->where('status', 'PUBLISHED')
                ->get()[0]->count;
        });
        $this->info('Cache for collections refreshed.');

        Cache::flexible('stats.citations', [172800, 259200], function () {
            return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
        });
        $this->info('Cache for citations refreshed.');

        Cache::flexible('stats.organisms', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
        });
        $this->info('Cache for organisms refreshed.');

        Cache::flexible('stats.geo_locations', [172800, 259200], function () {
            return DB::table('geo_locations')->selectRaw('count(*)')->get()[0]->count;
        });
        $this->info('Cache for geo locations refreshed.');

        Cache::flexible('stats.reports', [172800, 259200], function () {
            return DB::table('reports')->selectRaw('count(*)')->get()[0]->count;
        });
        $this->info('Cache for reports refreshed.');

        // Create the cache for all DashboardStatsMid widgets

        Cache::flexible('stats.molecules.non_stereo', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=false and is_parent=false')->get()[0]->count;
        });
        $this->info('Cache for molecules non-stereo refreshed.');

        Cache::flexible('stats.molecules.stereo', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=true')->get()[0]->count;
        });
        $this->info('Cache for molecules stereo refreshed.');

        Cache::flexible('stats.molecules.parent', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=false and is_parent=true')->get()[0]->count;
        });
        $this->info('Cache for molecules parent refreshed.');

        Cache::flexible('stats.molecules', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('active=true and NOT (is_parent=true AND has_variants=true)')->get()[0]->count;
        });
        $this->info('Cache for molecules refreshed.');

        // Create the cache for all Collection widgets

        $this->info('Processing collection wiget counts');
        $collection_ids = DB::select('SELECT id FROM collections order by id;');

        foreach ($collection_ids as $collection) {
            $this->info('Processing collection '.$collection->id);

            $entries = DB::select("SELECT doi, organism, geo_location FROM entries WHERE collection_id = {$collection->id};");

            $dois = [];
            $organisms = [];
            $geo_locations = [];

            foreach ($entries as $entry) {
                foreach (explode('|', $entry->doi) as $doi) {
                    if ($doi !== '') {
                        array_push($dois, $doi);
                    }
                }
                foreach (explode('|', $entry->organism) as $organism) {
                    if ($organism !== '') {
                        array_push($organisms, $organism);
                    }
                }
                foreach (explode('|', $entry->geo_location) as $geo_location) {
                    if ($geo_location !== '') {
                        array_push($geo_locations, $geo_location);
                    }
                }
            }
            $unique_dois_count = count(array_unique($dois));
            $unique_organisms_count = count(array_unique($organisms));
            $unique_geo_locations_count = count(array_unique($geo_locations));

            $total_entries = DB::select("SELECT count(*) FROM entries WHERE collection_id = {$collection->id};");
            $successful_entries = DB::select("SELECT count(*) FROM entries WHERE collection_id = {$collection->id} AND status = 'PASSED';");
            $failed_entries = DB::select("SELECT count(*) FROM entries WHERE collection_id = {$collection->id} AND status = 'REJECTED';");
            $molecules_count = DB::select("SELECT count(*) FROM collection_molecule WHERE collection_id = {$collection->id};");

            DB::statement(
                "UPDATE collections
            SET (total_entries, successful_entries, failed_entries, molecules_count, citations_count, organisms_count, geo_count) = ({$total_entries[0]->count}, {$successful_entries[0]->count}, {$failed_entries[0]->count}, {$molecules_count[0]->count}, {$unique_dois_count}, {$unique_organisms_count}, {$unique_geo_locations_count})
            WHERE id = {$collection->id};"
            );
        }

        $this->info('Processing collection wiget counts complete');
    }
}
