<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GeoLocationGlobeStatsService
{
    /**
     * @return array{
     *     countries: list<array{
     *         country_code: string,
     *         country: string,
     *         flag: string|null,
     *         molecules: int,
     *         organisms: int,
     *         geo_locations: int
     *     }>,
     *     totals: array{
     *         molecules: int,
     *         organisms: int,
     *         geo_locations: int,
     *         countries: int
     *     }
     * }
     */
    public function getCountryStats(): array
    {
        return Cache::flexible('stats.geo_globe.countries', [3600, 7200], function () {
            $rows = DB::select("
                SELECT
                    UPPER(gl.country_code) AS country_code,
                    MAX(gl.country) AS country,
                    MAX(gl.flag) AS flag,
                    COUNT(DISTINCT glm.molecule_id) AS molecules,
                    COUNT(DISTINCT mo.organism_id) AS organisms,
                    COUNT(DISTINCT gl.id) AS geo_locations
                FROM geo_locations gl
                INNER JOIN geo_location_molecule glm ON glm.geo_location_id = gl.id
                LEFT JOIN molecule_organism mo ON mo.molecule_id = glm.molecule_id
                    AND (mo.geo_location_id = gl.id OR mo.geo_location_id IS NULL)
                WHERE gl.country_code IS NOT NULL
                    AND gl.country_code <> ''
                GROUP BY UPPER(gl.country_code)
                ORDER BY molecules DESC
            ");

            $countries = array_map(function ($row) {
                return [
                    'country_code' => $row->country_code,
                    'country' => $row->country,
                    'flag' => $row->flag,
                    'molecules' => (int) $row->molecules,
                    'organisms' => (int) $row->organisms,
                    'geo_locations' => (int) $row->geo_locations,
                ];
            }, $rows);

            return [
                'countries' => $countries,
                'totals' => [
                    'molecules' => (int) DB::table('geo_location_molecule as glm')
                        ->join('geo_locations as gl', 'gl.id', '=', 'glm.geo_location_id')
                        ->whereNotNull('gl.country_code')
                        ->where('gl.country_code', '<>', '')
                        ->selectRaw('COUNT(DISTINCT glm.molecule_id) as aggregate')
                        ->value('aggregate'),
                    'organisms' => (int) DB::table('molecule_organism as mo')
                        ->join('geo_location_molecule as glm', 'glm.molecule_id', '=', 'mo.molecule_id')
                        ->join('geo_locations as gl', 'gl.id', '=', 'glm.geo_location_id')
                        ->whereNotNull('gl.country_code')
                        ->where('gl.country_code', '<>', '')
                        ->selectRaw('COUNT(DISTINCT mo.organism_id) as aggregate')
                        ->value('aggregate'),
                    'geo_locations' => (int) DB::table('geo_locations')
                        ->whereNotNull('country_code')
                        ->where('country_code', '<>', '')
                        ->count(),
                    'countries' => count($countries),
                ],
            ];
        });
    }
}
