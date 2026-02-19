<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildEntryMetaData extends Command
{
    protected $signature = 'coconut:rebuild-meta-data {collection? : Optional collection ID to scope the rebuild}';

    protected $description = 'Rebuild meta_data JSON for entries based on existing flat columns (doi, organism, locations, etc).';

    public function handle(): int
    {
        $chunkSize = 10000;
        $collectionId = $this->argument('collection');

        if ($collectionId) {
            $this->info("Scoping to collection ID: {$collectionId}");
        }

        // Replace '|' with ',' in geo_location for collection 40
        if (! $collectionId || $collectionId == 40) {
            $this->info('Replacing "|" with "," in geo_location for collection 40...');
            Entry::where('collection_id', 40)
                ->whereNotNull('geo_location')
                ->update(['geo_location' => DB::raw("REPLACE(geo_location, '|', ',')")]);
        }

        // Replace '|' with '##' in geo_location for collection 62
        if (! $collectionId || $collectionId == 62) {
            $this->info('Replacing "|" with "##" in geo_location for collection 62...');
            Entry::where('collection_id', 62)
                ->whereNotNull('geo_location')
                ->update(['geo_location' => DB::raw("REPLACE(geo_location, '|', '##')")]);
        }

        // Replace '|' with '##' in organism, doi, and link columns for all entries
        $this->info('Replacing "|" with "##" in organism, doi, and link columns...');

        $affected = 0;
        $replacementQuery = Entry::query()
            ->where(function ($q) {
                $q->where('organism', 'like', '%|%')
                    ->orWhere('doi', 'like', '%|%')
                    ->orWhere('link', 'like', '%|%');
            });

        if ($collectionId) {
            $replacementQuery->where('collection_id', $collectionId);
        }

        $replacementQuery->chunkById($chunkSize, function ($entries) use (&$affected) {
            foreach ($entries as $entry) {
                $updated = false;
                if ($entry->organism && str_contains($entry->organism, '|')) {
                    $entry->organism = str_replace('|', '##', $entry->organism);
                    $updated = true;
                }
                if ($entry->doi && str_contains($entry->doi, '|')) {
                    $entry->doi = str_replace('|', '##', $entry->doi);
                    $updated = true;
                }
                if ($entry->link && str_contains($entry->link, '|')) {
                    $entry->link = str_replace('|', '##', $entry->link);
                    $updated = true;
                }
                if ($updated) {
                    $entry->save();
                    $affected++;
                }
            }
        });
        $this->info("Done. Updated {$affected} entries.");

        $query = Entry::query();

        if ($collectionId) {
            $query->where('collection_id', $collectionId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No entries found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} entries ...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById($chunkSize, function ($entries) use ($bar) {
            /** @var \App\Models\Entry $entry */
            foreach ($entries as $entry) {
                $this->rebuildEntryMetaData($entry);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Done rebuilding meta_data.');

        return self::SUCCESS;
    }

    protected function rebuildEntryMetaData(Entry $entry): void
    {
        $collection = Collection::find($entry->collection_id);
        $hasMapping = (bool) optional($collection)->has_mapping;

        $flatDois = $this->flattenGroup($entry->doi ?? '', 'doi ');
        $flatOrganisms = $this->flattenGroup($entry->organism ?? '', 'organism');

        $nDois = count($flatDois);
        $nOrgs = count($flatOrganisms);

        if ($hasMapping && $nDois == $nOrgs) {
            $mappingStatus = 'full';
        } else {
            if ($nDois <= 1 || $nOrgs <= 1) {
                $mappingStatus = 'inferred';
            } else {
                $mappingStatus = 'ambiguous';
            }
        }

        $synonyms = is_array($entry->synonyms)
            ? $entry->synonyms
            : $this->processSynonyms((string) $entry->synonyms);

        $flatParts = $this->flattenGroup($entry->organism_part ?? '', 'organism_part');
        $flatGeoLocations = $this->flattenGroup($entry->geo_location ?? '', 'geo_location');
        $flatLocations = $this->flattenGroup($entry->location ?? '', 'location');

        $metaData = [
            'm' => [
                'smi' => $entry->canonical_smiles ?? '',
                'rid' => $entry->reference_id ?? '',
                'nm' => $entry->name ?? '',
                'syn' => $synonyms,
                'url' => $entry->link ?? '',
                'mol' => $entry->mol_filename ?? '',
                'cmt' => $entry->structural_comments ?? '',
                'ms' => $mappingStatus,
                'refs' => [],
                'dois' => array_values(array_unique($flatDois)),
                'orgs' => array_values(array_unique($flatOrganisms)),
                'prts' => array_values(array_unique($flatParts)),
                'geos' => array_values(array_unique($flatGeoLocations)),
                'ecos' => array_values(array_unique($flatLocations)),
            ],
        ];

        // $hasRelData = ! empty($entry->doi) || ! empty($entry->organism);

        // if ($hasRelData) {
        if ($mappingStatus === 'ambiguous') {
            $metaData['m']['refs'] = $this->buildAmbiguousReferences($entry);
        } elseif ($mappingStatus === 'inferred') {
            $metaData['m']['refs'] = $this->buildInferredReferences($entry);
        } else { // full
            $metaData['m']['refs'] = $this->buildFullReferences($entry);
        }
        // }

        $entry->meta_data = $metaData;
        $entry->save();
    }

    /**
     * FULL mapping: respect grouping semantics from old CSV
     * (groups separated by '##', pipe-separated values inside group)
     */
    protected function buildFullReferences(Entry $entry): array
    {
        $dois = $this->splitAndClean($entry->doi ?? '', '##');
        $organisms = $this->splitAndClean($entry->organism ?? '', '##');
        $organismParts = $this->splitAndClean($entry->organism_part ?? '', '##');
        $geoLocations = $this->splitAndClean($entry->geo_location ?? '', '##');
        $locations = $this->splitAndClean($entry->location ?? '', '##');

        $maxCount = max(
            count($dois),
            count($organisms),
            count($organismParts),
            count($geoLocations),
            count($locations)
        );

        $references = [];

        for ($i = 0; $i < $maxCount; $i++) {
            $doi = $dois[$i] ?? '';
            $organism = $organisms[$i] ?? '';
            $parts = $organismParts[$i] ?? '';
            $geoLocs = $geoLocations[$i] ?? '';
            $locs = $locations[$i] ?? '';

            if (empty($doi) && empty($organism)) {
                continue;
            }

            // Find or create reference for this DOI
            $referenceIndex = null;
            foreach ($references as $index => $ref) {
                if ($ref['doi'] === $doi) {
                    $referenceIndex = $index;
                    break;
                }
            }

            if ($referenceIndex === null) {
                $references[] = [
                    'doi' => $doi,
                    'orgs' => [],
                ];
                $referenceIndex = count($references) - 1;
            }

            if (! empty($organism)) {
                $organismData = [
                    'nm' => $organism,
                    'prts' => ! empty($parts) ? array_map('trim', explode('|', $parts)) : [],
                    'locs' => [],
                ];

                if (! empty($geoLocs)) {
                    $geoLocationsList = array_map('trim', explode('|', $geoLocs));
                    $locationsList = ! empty($locs) ? array_map('trim', explode('|', $locs)) : [];

                    foreach ($geoLocationsList as $locIndex => $geoLocation) {
                        if (! empty($geoLocation)) {
                            $ecosystems = [];
                            if (isset($locationsList[$locIndex]) && ! empty($locationsList[$locIndex])) {
                                $ecosystems = array_map('trim', explode(';', $locationsList[$locIndex]));
                            }

                            $organismData['locs'][] = [
                                'nm' => $geoLocation,
                                'ecos' => $ecosystems,
                            ];
                        }
                    }
                }

                $references[$referenceIndex]['orgs'][] = $organismData;
            }
        }

        return $references;
    }

    /**
     * INFERRED mapping: one side unambiguous (1 DOI or 1 organism)
     */
    protected function buildInferredReferences(Entry $entry): array
    {
        $dois = $this->flattenGroup($entry->doi ?? '', 'doi ');
        $organisms = $this->flattenGroup($entry->organism ?? '', 'organism');
        $parts = $this->flattenGroup($entry->organism_part ?? '', 'organism_part');
        $geoLocs = $this->flattenGroup($entry->geo_location ?? '', 'geo_location');
        $ecosystems = $this->flattenGroup($entry->location ?? '', 'location');
        $nDois = count($dois);
        $nOrgs = count($organisms);

        // 1 DOI, many organisms
        if ($nDois <= 1 && $nOrgs > 0) {
            $locationsStructured = [];
            foreach ($geoLocs as $geo) {
                $locationsStructured[] = [
                    'nm' => $geo,
                    'ecos' => $ecosystems,
                ];
            }

            $organismEntries = [];
            foreach ($organisms as $orgName) {
                $organismEntries[] = [
                    'nm' => $orgName,
                    'prts' => $parts,
                    'locs' => $locationsStructured,
                ];
            }

            return [[
                'doi' => $dois[0] ?? '',
                'orgs' => $organismEntries,
            ]];
        }

        // Many DOIs, 1 organism
        if ($nOrgs <= 1 && $nDois > 0) {
            $locationsStructured = [];
            foreach ($geoLocs as $geo) {
                $locationsStructured[] = [
                    'nm' => $geo,
                    'ecos' => $ecosystems,
                ];
            }

            $org = [
                'nm' => $organisms[0] ?? '',
                'prts' => $parts,
                'locs' => $locationsStructured,
            ];

            $refs = [];
            foreach ($dois as $doi) {
                $refs[] = [
                    'doi' => $doi ?? '',
                    'orgs' => [$org],
                ];
            }

            return $refs;
        }

        // Fallback (should be rare): just list DOIs
        return array_map(fn ($doi) => ['doi' => $doi, 'orgs' => []], $dois);
    }

    /**
     * AMBIGUOUS mapping: DOIs only, everything else in unresolved_metadata
     */
    protected function buildAmbiguousReferences(Entry $entry): array
    {
        $dois = $this->flattenGroup($entry->doi ?? '', 'doi ');

        $references = [];
        foreach ($dois as $doi) {
            $references[] = [
                'doi' => $doi,
                'orgs' => [],
            ];
        }

        return $references;
    }

    /**
     * Split and clean by a high-level separator (for grouped/full mapping).
     */
    protected function splitAndClean(string $data, string $separator): array
    {
        if (empty($data)) {
            return [];
        }

        return array_map('trim', explode($separator, $data));
    }

    /**
     * Flatten groups + inner separators into a flat unique list.
     * Treat "##", "|" and ";" as separators.
     */
    protected function flattenGroup(?string $data, $data_type): array
    {
        if (empty($data)) {
            return [''];
        }

        $parts = [];
        if ($data_type == 'doi ' || $data_type == 'organism') {
            $parts = preg_split('/(##)/', $data);
        } elseif ($data_type == 'organism_part' || $data_type == 'geo_location') {
            $parts = preg_split('/(##|\|)/', $data);
        } elseif ($data_type == 'location') {
            $parts = preg_split('/(##|\||;)/', $data);
        }

        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $result[] = $part;
            }
        }

        return $result;
        // return array_values(array_unique($result));
    }

    /**
     * Process pipe-separated synonyms into an array.
     */
    protected function processSynonyms(string $synonymsData): array
    {
        if (empty($synonymsData)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode('|', $synonymsData)),
            fn ($synonym) => ! empty($synonym)
        );
    }
}
