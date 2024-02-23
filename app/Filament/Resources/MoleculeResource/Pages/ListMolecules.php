<?php

namespace App\Filament\Resources\MoleculeResource\Pages;

use App\Filament\Resources\MoleculeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMolecules extends ListRecords
{
    protected static string $resource = MoleculeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
