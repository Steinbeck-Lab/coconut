<?php

namespace App\Services;

class GeoLocationEnrichmentResult
{
    public function __construct(
        public readonly string $country,
        public readonly string $countryCode,
        public readonly ?string $county = null,
        public readonly ?string $flag = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly string $method = 'segment',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        $attributes = [
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'county' => $this->county,
            'flag' => $this->flag,
        ];

        if ($this->latitude !== null) {
            $attributes['latitude'] = $this->latitude;
        }

        if ($this->longitude !== null) {
            $attributes['longitude'] = $this->longitude;
        }

        return $attributes;
    }
}
