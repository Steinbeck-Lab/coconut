<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Pages;

use App\Filament\Dashboard\Resources\MoleculeResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMolecules extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = MoleculeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
