<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Stats extends Component
{
    public $properties_json_data = [];

    public $bubble_frequency_json_data = [];

    // Statistics properties from Welcome page
    public $totalMolecules;

    public $totalCollections;

    public $uniqueOrganisms;

    public $citationsMapped;

    // New statistics properties
    public $organismsWithIri;

    public $moleculesWithOrganisms;

    public $moleculesWithCitations;

    public $distinctGeoLocations;

    public $moleculesWithGeolocations;

    public $revokedMolecules;

    public function mount()
    {
        // update the switch cases when you add new paths to this
        $plotDataFiles = [
            'reports/density_charts.json',
            'reports/bubble_frequency_charts.json',
        ];

        foreach ($plotDataFiles as $filePath) {
            $plotJson = public_path($filePath);

            try {
                if (! file_exists($plotJson)) {
                    throw new \Exception('Density chart data file not found');
                }

                $jsonContent = file_get_contents($plotJson);
                $decodedData = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Error decoding JSON data: '.json_last_error_msg());
                }

                // Store in the corresponding public properties - updade this when adding a new path
                switch ($filePath) {
                    case 'reports/density_charts.json':
                        $this->properties_json_data = $decodedData['properties'];
                        break;
                    case 'reports/bubble_frequency_charts.json':
                        $this->bubble_frequency_json_data = $decodedData;
                        break;
                    default:
                        break;
                }
            } catch (\Exception $e) {
                Log::error('Failed to load '.$filePath.' chart data: '.$e->getMessage());
            }
        }
    }

    public function render()
    {
        // Calculate statistics same as Welcome component
        $this->totalMolecules = Cache::flexible('stats.molecules', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('active=true and NOT (is_parent=true AND has_variants=true)')->get()[0]->count;
        });
        $this->totalCollections = Cache::flexible('stats.collections', [172800, 259200], function () {
            return DB::table('collections')->selectRaw('count(*)')->whereRaw("status = 'PUBLISHED'")->get()[0]->count;
        });
        $this->uniqueOrganisms = Cache::flexible('stats.organisms', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
        });
        $this->citationsMapped = Cache::flexible('stats.citations', [172800, 259200], function () {
            return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
        });

        // New statistics based on user's SQL queries
        $this->organismsWithIri = Cache::flexible('stats.organisms.with_iri', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('COUNT(DISTINCT(slug))')->whereNotNull('iri')->get()[0]->count;
        });

        $this->moleculesWithOrganisms = Cache::flexible('stats.molecules.with_organisms', [172800, 259200], function () {
            return DB::table('molecule_organism')->selectRaw('count(DISTINCT(molecule_id))')->get()[0]->count;
        });

        $this->moleculesWithCitations = Cache::flexible('stats.molecules.with_citations', [172800, 259200], function () {
            return DB::table('citables')->selectRaw('count(DISTINCT(citable_id))')->where('citable_type', 'App\Models\Molecule')->get()[0]->count;
        });

        $this->distinctGeoLocations = Cache::flexible('stats.geo_locations.distinct', [172800, 259200], function () {
            return DB::table('geo_locations')->selectRaw('count(DISTINCT name)')->get()[0]->count;
        });

        $this->moleculesWithGeolocations = Cache::flexible('stats.molecules.with_geolocations', [172800, 259200], function () {
            return DB::table('geo_location_molecule')->selectRaw('count(DISTINCT(molecule_id))')->get()[0]->count;
        });

        $this->revokedMolecules = Cache::flexible('stats.molecules.revoked', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->where('status', 'REVOKED')->get()[0]->count;
        });

        return view('livewire.stats', [
            'properties_json_data' => $this->properties_json_data,
            'bubble_frequency_json_data' => $this->bubble_frequency_json_data,
            'totalMolecules' => $this->totalMolecules,
            'totalCollections' => $this->totalCollections,
            'uniqueOrganisms' => $this->uniqueOrganisms,
            'citationsMapped' => $this->citationsMapped,
            'organismsWithIri' => $this->organismsWithIri,
            'moleculesWithOrganisms' => $this->moleculesWithOrganisms,
            'moleculesWithCitations' => $this->moleculesWithCitations,
            'distinctGeoLocations' => $this->distinctGeoLocations,
            'moleculesWithGeolocations' => $this->moleculesWithGeolocations,
            'revokedMolecules' => $this->revokedMolecules,
        ]);
    }
}
