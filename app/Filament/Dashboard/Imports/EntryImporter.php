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
}
