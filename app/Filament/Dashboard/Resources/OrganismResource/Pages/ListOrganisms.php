<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Pages;

use App\Filament\Dashboard\Resources\OrganismResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrganisms extends ListRecords
{
    protected static string $resource = OrganismResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
