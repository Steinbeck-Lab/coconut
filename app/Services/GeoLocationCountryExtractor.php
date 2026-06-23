<?php

namespace App\Services;

class GeoLocationCountryExtractor
{
    /** @var array<string, array{code: string, name: string}>|null */
    private ?array $lookup = null;

    /** @var list<array{name: string, len: int, code: string}>|null */
    private ?array $countryNamesByLength = null;

    public function extract(string $name): ?GeoLocationEnrichmentResult
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $parts = array_values(array_filter(
            array_map('trim', explode(',', $name)),
            fn (string $part) => $part !== ''
        ));

        for ($index = count($parts) - 1; $index >= 0; $index--) {
            $match = $this->matchLookup($parts[$index]);
            if ($match !== null) {
                $county = $index > 0 ? $parts[$index - 1] : null;

                return $this->buildResult($match, $county, 'segment');
            }
        }

        $wholeMatch = $this->matchLookup($name);
        if ($wholeMatch !== null) {
            return $this->buildResult($wholeMatch, null, 'whole');
        }

        if (preg_match('/\(([^)]+)\)\s*$/', $name, $matches)) {
            $state = strtolower(trim($matches[1]));
            if (in_array($state, $this->mexicanStates(), true)) {
                return $this->buildResult(
                    ['code' => 'mx', 'name' => 'Mexico'],
                    trim(preg_replace('/\([^)]+\)\s*$/', '', $name) ?? $name),
                    'parens_mx'
                );
            }
        }

        return $this->matchBySubstring($name);
    }

    public function shouldSkipGeocoding(string $name): bool
    {
        return (bool) preg_match(config('geo_location.geocode_skip_pattern'), $name);
    }

    /**
     * @return array{code: string, name: string}|null
     */
    private function matchLookup(string $value): ?array
    {
        $key = strtolower(trim($value));

        return $this->lookup()[$key] ?? null;
    }

    private function matchBySubstring(string $name): ?GeoLocationEnrichmentResult
    {
        $minLength = (int) config('geo_location.substring_min_length', 4);

        foreach ($this->countryNamesByLength() as $country) {
            if ($country['len'] < $minLength) {
                continue;
            }

            if (stripos($name, $country['name']) !== false) {
                return $this->buildResult(
                    ['code' => $country['code'], 'name' => $country['name']],
                    null,
                    'substring'
                );
            }
        }

        return null;
    }

    /**
     * @param  array{code: string, name: string}  $match
     */
    private function buildResult(array $match, ?string $county, string $method): GeoLocationEnrichmentResult
    {
        $countryData = country(strtolower($match['code']));

        return new GeoLocationEnrichmentResult(
            country: $countryData ? $countryData->getName() : $match['name'],
            countryCode: strtoupper($match['code']),
            county: $county,
            flag: $countryData ? $countryData->getEmoji() : null,
            method: $method,
        );
    }

    /**
     * @return array<string, array{code: string, name: string}>
     */
    private function lookup(): array
    {
        if ($this->lookup !== null) {
            return $this->lookup;
        }

        $lookup = [];

        foreach (countries() as $code => $data) {
            $common = $data['name']['common'] ?? (is_string($data['name'] ?? null) ? $data['name'] : null);

            if (! $common) {
                continue;
            }

            $lookup[strtolower(trim($common))] = [
                'code' => strtolower((string) $code),
                'name' => $common,
            ];
        }

        foreach (config('geo_location.aliases', []) as $alias => $code) {
            $countryData = country(strtolower((string) $code));

            if ($countryData) {
                $lookup[strtolower((string) $alias)] = [
                    'code' => strtolower((string) $code),
                    'name' => $countryData->getName(),
                ];
            }
        }

        $lookup['antarctica'] = ['code' => 'aq', 'name' => 'Antarctica'];

        $this->lookup = $lookup;

        return $this->lookup;
    }

    /**
     * @return list<array{name: string, len: int, code: string}>
     */
    private function countryNamesByLength(): array
    {
        if ($this->countryNamesByLength !== null) {
            return $this->countryNamesByLength;
        }

        $names = [];

        foreach (countries() as $code => $data) {
            $common = $data['name']['common'] ?? (is_string($data['name'] ?? null) ? $data['name'] : null);

            if (! $common) {
                continue;
            }

            $names[] = [
                'name' => $common,
                'len' => strlen($common),
                'code' => strtolower((string) $code),
            ];
        }

        usort($names, fn (array $a, array $b) => $b['len'] <=> $a['len']);

        $this->countryNamesByLength = $names;

        return $this->countryNamesByLength;
    }

    /**
     * @return list<string>
     */
    private function mexicanStates(): array
    {
        return config('geo_location.mexican_states', []);
    }
}
