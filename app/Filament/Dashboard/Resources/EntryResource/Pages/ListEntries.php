<?php

namespace App\Filament\Dashboard\Resources\EntryResource\Pages;

use App\Filament\Dashboard\Resources\EntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntries extends ListRecords
{
    protected static string $resource = EntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
