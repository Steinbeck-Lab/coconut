<?php

namespace App\Services;

use App\Models\GeoLocation;

class GeoLocationService
{
    public function __construct(
        private readonly GeoLocationEnricher $enricher,
    ) {}

    /**
     * @param  array<string, int>|null  $cache
     */
    public function findOrCreate(string $name, ?array &$cache = null, bool $allowGeocoding = true): GeoLocation
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Geo location name cannot be empty.');
        }

        if ($cache !== null && isset($cache[$name])) {
            return GeoLocation::findOrFail($cache[$name]);
        }

        $geoLocation = GeoLocation::firstOrCreate(['name' => $name]);

        if (blank($geoLocation->country_code)) {
            $this->enricher->enrich($geoLocation, $allowGeocoding);
        }

        if ($cache !== null) {
            $cache[$name] = $geoLocation->id;
        }

        return $geoLocation;
    }

    /**
     * @param  array<string, int>|null  $cache
     */
    public function findOrCreateId(string $name, ?array &$cache = null, bool $allowGeocoding = true): ?int
    {
        if (trim($name) === '') {
            return null;
        }

        return $this->findOrCreate($name, $cache, $allowGeocoding)->id;
    }
}
