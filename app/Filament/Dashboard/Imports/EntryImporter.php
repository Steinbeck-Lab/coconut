<?php

namespace App\Filament\Dashboard\Imports;

use App\Events\ImportedCSVProcessed;
use App\Models\Collection;
use App\Models\Entry;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Checkbox;

class EntryImporter extends Importer
{
    protected static ?string $model = Entry::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('canonical_smiles'),
            ImportColumn::make('reference_id'),
            ImportColumn::make('name'),
            ImportColumn::make('synonyms'),
            ImportColumn::make('doi'),
            ImportColumn::make('link'),
            ImportColumn::make('organism'),
            ImportColumn::make('organism_part'),
            ImportColumn::make('coconut_id'),
            ImportColumn::make('mol_filename'),
            ImportColumn::make('structural_comments'),
            ImportColumn::make('geo_location'),
            ImportColumn::make('location'),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Checkbox::make('updateExisting')
                ->label('Update existing records'),
        ];
    }

    public function resolveRecord(): ?Entry
    {
        if ($this->options['updateExisting'] ?? false) {
            return Entry::firstOrNew([
                'canonical_smiles' => $this->data['canonical_smiles'],
                'reference_id' => $this->data['reference_id'],
                'collection_id' => $this->options['collection_id'],
            ]);
        }

        $entry = new Entry;
        $entry->collection_id = $this->options['collection_id'];

        return $entry;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        ImportedCSVProcessed::dispatch($import);

        $body = 'Your entry import has completed. '.number_format($import->total_rows).' '.str('row')->plural($import->total_rows).' imported.';

        return $body;
    }

    protected function beforeSave(): void
    {
        // Convert flattened data to structured JSON for meta_data
        $this->record->meta_data = $this->reconstructMetaData();

        // Process synonyms and store them in the synonyms field
        $this->record->synonyms = $this->processSynonyms($this->data['synonyms'] ?? '');

        // Set default values
        $this->record->submission_type = 'csv';
        $this->record->status = 'SUBMITTED';
    }

    /**
     * Reconstruct the meta_data JSON structure from flattened CSV data
     */
    private function reconstructMetaData(): array
    {
        $collection = Collection::find($this->options['collection_id']);
        $hasMapping = (bool) optional($collection)->has_mapping;

        $flatDois = $this->flattenGroup($this->data['doi'] ?? '', 'doi ');
        $flatOrganisms = $this->flattenGroup($this->data['organism'] ?? '', 'organism');

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

        $synonyms = $this->processSynonyms($this->data['synonyms'] ?? '');

        $flatParts = $this->flattenGroup($this->data['organism_part'] ?? '', 'organism_part');
        $flatGeoLocations = $this->flattenGroup($this->data['geo_location'] ?? '', 'geo_location');
        $flatLocations = $this->flattenGroup($this->data['location'] ?? '', 'location');

        $metaData = [
            'm' => [
                'smi' => $this->data['canonical_smiles'] ?? '',
                'rid' => $this->data['reference_id'] ?? '',
                'nm' => $this->data['name'] ?? '',
                'syn' => $synonyms,
                'url' => $this->data['link'] ?? '',
                'mol' => $this->data['mol_filename'] ?? '',
                'cmt' => $this->data['structural_comments'] ?? '',
                'ms' => $mappingStatus,
                'refs' => [],
                'dois' => array_values(array_unique($flatDois)),
                'orgs' => array_values(array_unique($flatOrganisms)),
                'prts' => array_values(array_unique($flatParts)),
                'geos' => array_values(array_unique($flatGeoLocations)),
                'ecos' => array_values(array_unique($flatLocations)),
            ],
        ];

        if ($mappingStatus === 'ambiguous') {
            $metaData['m']['refs'] = $this->buildAmbiguousReferences();
        } elseif ($mappingStatus === 'inferred') {
            $metaData['m']['refs'] = $this->buildInferredReferences();
        } else {
            $metaData['m']['refs'] = $this->buildFullReferences();
        }

        return $metaData;
    }

    /**
     * FULL mapping: respect grouping semantics from CSV
     * (groups separated by '##', pipe-separated values inside group)
     */
    private function buildFullReferences(): array
    {
        $dois = $this->splitAndClean($this->data['doi'] ?? '', '##');
        $organisms = $this->splitAndClean($this->data['organism'] ?? '', '##');
        $organismParts = $this->splitAndClean($this->data['organism_part'] ?? '', '##');
        $geoLocations = $this->splitAndClean($this->data['geo_location'] ?? '', '##');
        $locations = $this->splitAndClean($this->data['location'] ?? '', '##');

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
    private function buildInferredReferences(): array
    {
        $dois = $this->flattenGroup($this->data['doi'] ?? '', 'doi ');
        $organisms = $this->flattenGroup($this->data['organism'] ?? '', 'organism');
        $parts = $this->flattenGroup($this->data['organism_part'] ?? '', 'organism_part');
        $geoLocs = $this->flattenGroup($this->data['geo_location'] ?? '', 'geo_location');
        $ecosystems = $this->flattenGroup($this->data['location'] ?? '', 'location');
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

        // Fallback: just list DOIs
        return array_map(fn ($doi) => ['doi' => $doi, 'orgs' => []], $dois);
    }

    /**
     * AMBIGUOUS mapping: DOIs only, everything else unresolvable
     */
    private function buildAmbiguousReferences(): array
    {
        $dois = $this->flattenGroup($this->data['doi'] ?? '', 'doi ');

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
    private function splitAndClean(string $data, string $separator): array
    {
        if (empty($data)) {
            return [];
        }

        return array_map('trim', explode($separator, $data));
    }

    /**
     * Flatten groups + inner separators into a flat list.
     * Treat "##", "|" and ";" as separators depending on data type.
     */
    private function flattenGroup(?string $data, string $dataType): array
    {
        if (empty($data)) {
            return [''];
        }

        $parts = [];
        if ($dataType == 'doi ' || $dataType == 'organism') {
            $parts = preg_split('/(##)/', $data);
        } elseif ($dataType == 'organism_part' || $dataType == 'geo_location') {
            $parts = preg_split('/(##|\|)/', $data);
        } elseif ($dataType == 'location') {
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
    }

    /**
     * Process pipe-separated synonyms into an array
     */
    private function processSynonyms(string $synonymsData): array
    {
        if (empty($synonymsData)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode('|', $synonymsData)),
            function ($synonym) {
                return ! empty($synonym);
            }
        );
    }

    public function getJobQueue(): ?string
    {
        return 'import';
    }
}
