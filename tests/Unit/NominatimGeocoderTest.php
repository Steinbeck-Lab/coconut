<?php

namespace Tests\Unit;

use App\Services\GeoLocationCountryExtractor;
use App\Services\NominatimGeocoder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NominatimGeocoderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        NominatimGeocoder::resetRateLimitState();
        Cache::flush();
    }

    public function test_geocodes_location_from_nominatim_response(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '42.6977',
                    'lon' => '23.3219',
                    'address' => [
                        'country_code' => 'bg',
                        'country' => 'Bulgaria',
                        'state' => 'Sofia City',
                    ],
                ],
            ]),
        ]);

        $geocoder = new NominatimGeocoder(new GeoLocationCountryExtractor);
        $result = $geocoder->geocode('Sofia, Bulgaria');

        $this->assertNotNull($result);
        $this->assertSame('Bulgaria', $result->country);
        $this->assertSame('BG', $result->countryCode);
        $this->assertSame('Sofia City', $result->county);
        $this->assertSame('geocoded', $result->method);
        $this->assertSame(42.6977, $result->latitude);
        $this->assertSame(23.3219, $result->longitude);
    }

    public function test_skips_geocoding_for_placeholder_names(): void
    {
        Http::fake();

        $geocoder = new NominatimGeocoder(new GeoLocationCountryExtractor);
        $result = $geocoder->geocode('Not specified(Ciudad de Mexico)');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_returns_null_when_nominatim_has_no_results(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([]),
        ]);

        $geocoder = new NominatimGeocoder(new GeoLocationCountryExtractor);
        $result = $geocoder->geocode('Tyrrhenian Sea');

        $this->assertNull($result);
    }
}
