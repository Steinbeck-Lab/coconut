<?php

namespace App\Filament\Dashboard\Resources\CitationResource\Pages;

use App\Filament\Dashboard\Resources\CitationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCitation extends EditRecord
{
    protected static string $resource = CitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
