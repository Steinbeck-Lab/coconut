<?php

namespace App\Filament\Dashboard\Resources\CitationResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Dashboard\Resources\CitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCitations extends ListRecords
{
    protected static string $resource = CitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
