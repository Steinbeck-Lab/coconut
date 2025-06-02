<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DetailedAnalytics extends Widget
{
    protected static string $view = 'filament.dashboard.widgets.detailed-analytics';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getAnalyticsData(): array
    {
        // Get main counts for percentage calculations
        $totalMolecules = Cache::flexible('stats.molecules', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('active=true and NOT (is_parent=true AND has_variants=true)')->get()[0]->count;
        });

        $uniqueOrganisms = Cache::flexible('stats.organisms', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
        });

        // Detailed analytics
        $organismsWithIri = Cache::flexible('stats.organisms.with_iri', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('COUNT(DISTINCT(slug))')->whereNotNull('iri')->get()[0]->count;
        });

        $moleculesWithOrganisms = Cache::flexible('stats.molecules.with_organisms', [172800, 259200], function () {
            return DB::table('molecule_organism')->selectRaw('count(DISTINCT(molecule_id))')->get()[0]->count;
        });

        $moleculesWithCitations = Cache::flexible('stats.molecules.with_citations', [172800, 259200], function () {
            return DB::table('citables')->selectRaw('count(DISTINCT(citable_id))')->where('citable_type', 'App\Models\Molecule')->get()[0]->count;
        });

        $distinctGeoLocations = Cache::flexible('stats.geo_locations.distinct', [172800, 259200], function () {
            return DB::table('geo_locations')->selectRaw('count(DISTINCT name)')->get()[0]->count;
        });

        $moleculesWithGeolocations = Cache::flexible('stats.molecules.with_geolocations', [172800, 259200], function () {
            return DB::table('geo_location_molecule')->selectRaw('count(DISTINCT(molecule_id))')->get()[0]->count;
        });

        $revokedMolecules = Cache::flexible('stats.molecules.revoked', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->where('status', 'REVOKED')->get()[0]->count;
        });

        return [
            'organisms_iri' => [
                'value' => number_format($organismsWithIri),
                'with_feature' => $organismsWithIri,
                'without_feature' => max($uniqueOrganisms - $organismsWithIri, 0),
                'percentage' => round(($organismsWithIri / max($uniqueOrganisms, 1)) * 100, 1),
                'description' => '% of total organisms',
                'icon' => 'heroicon-m-link',
                'color' => 'blue',
            ],
            'molecules_organisms' => [
                'value' => number_format($moleculesWithOrganisms),
                'with_feature' => $moleculesWithOrganisms,
                'without_feature' => max($totalMolecules - $moleculesWithOrganisms, 0),
                'percentage' => round(($moleculesWithOrganisms / max($totalMolecules, 1)) * 100, 1),
                'description' => '% of total molecules',
                'icon' => 'heroicon-m-arrow-right-circle',
                'color' => 'green',
            ],
            'molecules_citations' => [
                'value' => number_format($moleculesWithCitations),
                'with_feature' => $moleculesWithCitations,
                'without_feature' => max($totalMolecules - $moleculesWithCitations, 0),
                'percentage' => round(($moleculesWithCitations / max($totalMolecules, 1)) * 100, 1),
                'description' => '% of total molecules',
                'icon' => 'heroicon-m-document-text',
                'color' => 'purple',
            ],
            'distinct_geo_locations' => [
                'value' => number_format($distinctGeoLocations),
                'with_feature' => null,
                'without_feature' => null,
                'percentage' => null,
                'description' => 'Unique geographic locations',
                'icon' => 'heroicon-m-map-pin',
                'color' => 'cyan',
            ],
            'molecules_geolocations' => [
                'value' => number_format($moleculesWithGeolocations),
                'with_feature' => $moleculesWithGeolocations,
                'without_feature' => max($totalMolecules - $moleculesWithGeolocations, 0),
                'percentage' => round(($moleculesWithGeolocations / max($totalMolecules, 1)) * 100, 1),
                'description' => '% of total molecules',
                'icon' => 'heroicon-m-globe-alt',
                'color' => 'indigo',
            ],
            'revoked_molecules' => [
                'value' => number_format($revokedMolecules),
                'with_feature' => $revokedMolecules,
                'without_feature' => max($totalMolecules, 0),
                'percentage' => null,
                'description' => number_format($revokedMolecules).' molecules have been revoked',
                'icon' => 'heroicon-m-x-circle',
                'color' => 'red',
            ],
        ];
    }
}
