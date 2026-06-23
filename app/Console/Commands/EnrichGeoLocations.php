<?php

namespace App\Console\Commands;

use App\Models\GeoLocation;
use App\Services\GeoLocationEnricher;
use Illuminate\Console\Command;

class EnrichGeoLocations extends Command
{
    protected $signature = 'coconut:enrich-geo-locations
                            {--dry-run : Report only, no writes}
                            {--rules-only : Skip Nominatim geocoding}
                            {--geocode-only : Only geocode rows missing country_code}
                            {--force : Re-enrich even if country_code is set}
                            {--limit= : Cap rows processed (for testing)}';

    protected $description = 'Extract country metadata from geo location names using rules and Nominatim geocoding';

    public function handle(GeoLocationEnricher $enricher): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rulesOnly = (bool) $this->option('rules-only');
        $geocodeOnly = (bool) $this->option('geocode-only');
        $force = (bool) $this->option('force');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $query = GeoLocation::query()->orderBy('id');

        if (! $force) {
            $query->whereNull('country_code');
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $records = $query->get();
        $total = $records->count();

        if ($total === 0) {
            $this->info('No geo locations to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} geo location(s)...");

        $stats = [
            'segment' => 0,
            'whole' => 0,
            'parens_mx' => 0,
            'substring' => 0,
            'geocoded' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $allowGeocoding = ! $rulesOnly;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($records as $geoLocation) {
            if (! $force && filled($geoLocation->country_code)) {
                $stats['skipped']++;
                $bar->advance();

                continue;
            }

            if ($dryRun) {
                $result = $enricher->resolve(
                    $geoLocation->name,
                    allowGeocoding: $allowGeocoding,
                    geocodeOnly: $geocodeOnly,
                );
            } else {
                $result = $enricher->enrich(
                    $geoLocation,
                    allowGeocoding: $allowGeocoding,
                    force: $force,
                    geocodeOnly: $geocodeOnly,
                );
            }

            if ($result !== null) {
                $method = $result->method === 'geocoded' ? 'geocoded' : $result->method;
                $stats[$method] = ($stats[$method] ?? 0) + 1;
            } else {
                $stats['failed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $matched = $stats['segment'] + $stats['whole'] + $stats['parens_mx'] + $stats['substring'] + $stats['geocoded'];

        $this->table(
            ['Method', 'Count'],
            collect($stats)->map(fn (int $count, string $method) => [$method, $count])->values()->all(),
        );

        $this->info("Matched: {$matched} / {$total}");

        if ($dryRun) {
            $this->warn('Dry run only — no records were updated.');
        }

        return self::SUCCESS;
    }
}
