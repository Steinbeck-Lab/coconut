<?php

namespace App\Filament\Dashboard\Imports;

use App\Events\ImportedCSVProcessed;
use App\Models\Entry;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class EntryImporter extends Importer
{
    protected static ?string $model = Entry::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('canonical_smiles'),
            ImportColumn::make('reference_id'),
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

    public function resolveRecord(): ?Entry
    {
        // return Entry::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);
        $entry = new Entry();
        $entry->collection_id = $this->options['collection_id'];

        return $entry;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        ImportedCSVProcessed::dispatch($import);

        $body = 'Your entry import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
