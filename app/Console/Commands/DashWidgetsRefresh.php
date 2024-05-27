<?php

namespace App\Console\Commands;

use App\Models\Citation;
use App\Models\Collection;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Models\Report;
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
        // Clear the cache for all widgets
        Cache::forget('stats');

        // Create the cache for all DashboardStats widgets
       Cache::rememberForever('stats.collections', function () {
            return DB::table('collections')->selectRaw('count(*)')->get()[0]->count;
        });
        Cache::rememberForever('stats.citations', function () {
            return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
        });

        Cache::rememberForever('stats.organisms', function () {
            return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
        });
        Cache::rememberForever('stats.geo_locations', function () {
            return DB::table('geo_locations')->selectRaw('count(*)')->get()[0]->count;
        });
        Cache::rememberForever('stats.reports', function () {
            return DB::table('reports')->selectRaw('count(*)')->get()[0]->count;
        });



        // Create the cache for all DashboardStatsMid widgets

        Cache::rememberForever('stats.molecules', function () {
            return DB::table('molecules')->selectRaw('count(*)')->get()[0]->count;
        });
        Cache::rememberForever('stats.molecules.non_stereo', function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=false and is_parent=false')->get()[0]->count;
        });
       Cache::rememberForever('stats.molecules.stereo', function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=true')->get()[0]->count;
        });
        Cache::rememberForever('stats.molecules.parent', function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('has_stereo=false and is_parent=true')->get()[0]->count;
        });

        // Create the cache for all Collection widgets
        
        $collection_ids = Collection::pluck('id')->toArray();

        foreach ($collection_ids as $collection_id) {
            Cache::rememberForever('stats.collections'.$collection_id.'entries.count', function () use ($collection_id){
                return DB::table('entries')->selectRaw('count(*)')->whereRaw('collection_id='.$collection_id)->get()[0]->count;
            });
           Cache::rememberForever('stats.collections'.$collection_id.'passed_entries.count', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw("status = 'PASSED'")->get()[0]->count;
            });
            Cache::rememberForever('stats.collections'.$collection_id.'rejected_entries.count', function () {
                return DB::table('entries')->selectRaw('count(*)')->whereRaw("status = 'REJECTED'")->get()[0]->count;
            });
           Cache::rememberForever('stats.collections'.$collection_id.'molecules.count', function () use ($collection_id){
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw("collection_id =".$collection_id)->get()[0]->count;
            });
           Cache::rememberForever('stats.collections'.$collection_id.'citations.count', function () use ($collection_id){
                return DB::table('citables')->selectRaw('count(*)')->whereRaw("citable_type='App\Models\Collection' and citable_id=".$collection_id)->get()[0]->count;
            });
            Cache::rememberForever('stats.collections'.$collection_id.'organisms.count', function () use ($collection_id){
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw('collection_id='.$collection_id)->Join('molecule_organism', 'collection_molecule.molecule_id', '=', 'molecule_organism.molecule_id')->get()[0]->count;
            });
            Cache::rememberForever('stats.collections'.$collection_id.'geo_locations.count', function () use ($collection_id){
                return DB::table('collection_molecule')->selectRaw('count(*)')->whereRaw('collection_id='.$collection_id)->Join('geo_location_molecule', 'collection_molecule.molecule_id', '=', 'geo_location_molecule.molecule_id')->get()[0]->count;
            });
        }

    //     // Create the cache for all Molecule widgets

    //     $molecule_ids = Molecule::pluck('id')->toArray();

    //     foreach ($molecule_ids as $molecule_id) {
    //         Cache::rememberForever('stats.molecules'.$molecule_id.'organisms.count', function () use ($molecule_id){
    //             return DB::table('molecule_organism')->selectRaw('count(*)')->whereRaw('molecule_id='.$molecule_id)->get()[0]->count;
    //         });
    //         Cache::rememberForever('stats.molecules'.$molecule_id.'geo_locations.count', function () use ($molecule_id){
    //             return DB::table('geo_location_molecule')->selectRaw('count(*)')->whereRaw('molecule_id='.$molecule_id)->get()[0]->count;
    //         });
    //     }

        // // Create the cache for all Organism widgets

        // $organism_ids = Organism::pluck('id')->toArray();

        // foreach ($organism_ids as $organism_id) {
        //     Cache::rememberForever('stats.organisms'.$organism_id.'molecules.count', function () use ($organism_id){
        //         return DB::table('molecule_organism')->selectRaw('count(*)')->whereRaw('organism_id='.$organism_id)->get()[0]->count;
        //     });
        //     Cache::rememberForever('stats.organisms'.$organism_id.'geo_locations.count', function () use ($organism_id){
        //         return DB::table('molecule_organism')->selectRaw('count(*)')->whereRaw('organism_id='.$organism_id)->Join('geo_location_molecule', 'molecule_organism.molecule_id', '=', 'geo_location_molecule.molecule_id')->get()[0]->count;
        //     });
        // }

        // Create the cache for all Geo Location widgets

        $geo_location_ids = GeoLocation::pluck('id')->toArray();

        foreach ($geo_location_ids as $geo_location_id) {
            Cache::rememberForever('stats.geo_locations'.$geo_location_id.'molecules.count', function () use ($geo_location_id){
                return DB::table('geo_location_molecule')->selectRaw('count(*)')->whereRaw('geo_location_id='.$geo_location_id)->get()[0]->count;
            });
            Cache::rememberForever('stats.geo_locations'.$geo_location_id.'organisms.count', function () use ($geo_location_id){
                return DB::table('geo_location_molecule')->selectRaw('count(*)')->whereRaw('geo_location_id='.$geo_location_id)->Join('molecule_organism', 'geo_location_molecule.molecule_id', '=', 'molecule_organism.molecule_id')->get()[0]->count;
            });
        }

    }
}
