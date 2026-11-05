<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NominatimGeocoder
{
    private static ?float $lastRequestAt = null;

    public function __construct(
        private readonly GeoLocationCountryExtractor $extractor,
    ) {}

    public function geocode(string $name): ?GeoLocationEnrichmentResult
    {
        $name = trim($name);

        if ($name === '' || $this->extractor->shouldSkipGeocoding($name)) {
            return null;
        }

        $cacheKey = 'nominatim:'.md5($name);
        $ttl = (int) config('geo_location.nominatim_cache_ttl', 60 * 60 * 24 * 30);

        return Cache::remember($cacheKey, $ttl, function () use ($name) {
            return $this->requestGeocode($name);
        });
    }

    private function requestGeocode(string $name): ?GeoLocationEnrichmentResult
    {
        $this->respectRateLimit();

        $baseUrl = rtrim((string) config('services.nominatim.url'), '/');
        $userAgent = (string) config('services.nominatim.user_agent', 'COCONUT/1.0');

        try {
            $response = Http::withHeaders([
                'User-Agent' => $userAgent,
                'Accept' => 'application/json',
            ])->timeout(30)->get("{$baseUrl}/search", [
                'q' => $name,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1,
            ]);

            self::$lastRequestAt = microtime(true);

            if (! $response->successful()) {
                Log::warning('Nominatim geocoding failed', [
                    'name' => $name,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $results = $response->json();

            if (! is_array($results) || $results === []) {
                return null;
            }

            return $this->parseResult($results[0]);
        } catch (\Throwable $exception) {
            Log::warning('Nominatim geocoding exception', [
                'name' => $name,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function parseResult(array $result): ?GeoLocationEnrichmentResult
    {
        $address = $result['address'] ?? [];

        if (! is_array($address)) {
            return null;
        }

        $countryCode = strtolower((string) ($address['country_code'] ?? ''));

        if ($countryCode === '') {
            return null;
        }

        $countryData = country($countryCode);
        $countryName = (string) ($address['country'] ?? ($countryData ? $countryData->getName() : ''));

        if ($countryName === '') {
            return null;
        }

        $county = $address['state']
            ?? $address['county']
            ?? $address['region']
            ?? $address['province']
            ?? null;

        return new GeoLocationEnrichmentResult(
            country: $countryName,
            countryCode: strtoupper($countryCode),
            county: is_string($county) ? $county : null,
            flag: $countryData ? $countryData->getEmoji() : null,
            latitude: isset($result['lat']) ? (float) $result['lat'] : null,
            longitude: isset($result['lon']) ? (float) $result['lon'] : null,
            method: 'geocoded',
        );
    }

    private function respectRateLimit(): void
    {
        $delayMs = (int) config('services.nominatim.delay_ms', 1100);

        if (self::$lastRequestAt === null) {
            return;
        }

        $elapsedMs = (microtime(true) - self::$lastRequestAt) * 1000;

        if ($elapsedMs < $delayMs) {
            usleep((int) (($delayMs - $elapsedMs) * 1000));
        }
    }

    public static function resetRateLimitState(): void
    {
        self::$lastRequestAt = null;
    }
}
