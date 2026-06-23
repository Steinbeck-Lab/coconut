<?php

namespace App\Services;

use App\Models\GeoLocation;

class GeoLocationEnricher
{
    public function __construct(
        private readonly GeoLocationCountryExtractor $extractor,
        private readonly NominatimGeocoder $geocoder,
    ) {}

    public function resolve(string $name, bool $allowGeocoding = true, bool $geocodeOnly = false): ?GeoLocationEnrichmentResult
    {
        if (! $geocodeOnly) {
            $result = $this->extractor->extract($name);

            if ($result !== null) {
                return $result;
            }
        }

        if (! $allowGeocoding) {
            return null;
        }

        return $this->geocoder->geocode($name);
    }

    public function enrich(
        GeoLocation $geoLocation,
        bool $allowGeocoding = true,
        bool $force = false,
        bool $geocodeOnly = false,
    ): ?GeoLocationEnrichmentResult {
        if (! $force && filled($geoLocation->country_code)) {
            return null;
        }

        $result = $this->resolve($geoLocation->name, $allowGeocoding, $geocodeOnly);

        if ($result === null) {
            return null;
        }

        $this->applyResult($geoLocation, $result, $force);
        $geoLocation->save();

        return $result;
    }

    public function applyResult(GeoLocation $geoLocation, GeoLocationEnrichmentResult $result, bool $force = false): void
    {
        foreach ($result->toModelAttributes() as $attribute => $value) {
            if ($value === null) {
                continue;
            }

            if ($force || blank($geoLocation->{$attribute})) {
                $geoLocation->{$attribute} = $value;
            }
        }
    }
}
