<?php

namespace App\Filament\Dashboard\Imports;

use App\Events\ImportedCSVProcessed;
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
        $metaData = [
            'new_molecule_data' => [
                'canonical_smiles' => $this->data['canonical_smiles'] ?? '',
                'reference_id' => $this->data['reference_id'] ?? '',
                'name' => $this->data['name'] ?? '',
                'synonyms' => $this->processSynonyms($this->data['synonyms'] ?? ''),
                'link' => $this->data['link'] ?? '',
                'mol_filename' => $this->data['mol_filename'] ?? '',
                'structural_comments' => $this->data['structural_comments'] ?? '',
                'references' => [],
            ],
        ];

        // Process relationships if they exist
        if (! empty($this->data['doi']) || ! empty($this->data['organism'])) {
            $metaData['new_molecule_data']['references'] = $this->buildReferences();
        }

        return $metaData;
    }

    /**
     * Build the references array from flattened relationship data
     */
    private function buildReferences(): array
    {
        // Split the flattened data by organism separator ##
        $dois = $this->splitAndClean($this->data['doi'] ?? '', '##');
        $organisms = $this->splitAndClean($this->data['organism'] ?? '', '##');
        $organismParts = $this->splitAndClean($this->data['organism_part'] ?? '', '##');
        $geoLocations = $this->splitAndClean($this->data['geo_location'] ?? '', '##');
        $locations = $this->splitAndClean($this->data['location'] ?? '', '##');

        // Get the maximum count to handle all organisms
        $maxCount = max(
            count($dois),
            count($organisms),
            count($organismParts),
            count($geoLocations),
            count($locations)
        );

        $references = [];
        $processedDois = [];

        for ($i = 0; $i < $maxCount; $i++) {
            $doi = $dois[$i] ?? '';
            $organism = $organisms[$i] ?? '';
            $parts = $organismParts[$i] ?? '';
            $geoLocs = $geoLocations[$i] ?? '';
            $locs = $locations[$i] ?? '';

            // Skip if no meaningful data
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
                    'organisms' => [],
                ];
                $referenceIndex = count($references) - 1;
            }

            // Build organism data
            if (! empty($organism)) {
                $organismData = [
                    'name' => $organism,
                    'parts' => ! empty($parts) ? explode('|', $parts) : [],
                    'locations' => [],
                ];

                // Build locations if they exist
                if (! empty($geoLocs)) {
                    $geoLocationsList = explode('|', $geoLocs);
                    $locationsList = ! empty($locs) ? explode('|', $locs) : [];

                    foreach ($geoLocationsList as $locIndex => $geoLocation) {
                        if (! empty($geoLocation)) {
                            $ecosystems = [];
                            if (isset($locationsList[$locIndex]) && ! empty($locationsList[$locIndex])) {
                                $ecosystems = explode(';', $locationsList[$locIndex]);
                            }

                            $organismData['locations'][] = [
                                'name' => $geoLocation,
                                'ecosystems' => $ecosystems,
                            ];
                        }
                    }
                }
                $references[$referenceIndex]['organisms'][] = $organismData;
            }
        }

        return $references;
    }

    /**
     * Helper function to split and clean data
     */
    private function splitAndClean(string $data, string $separator): array
    {
        if (empty($data)) {
            return [];
        }

        return array_map('trim', explode($separator, $data));
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
