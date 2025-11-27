<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Dashboard\Resources\MoleculeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMolecule extends EditRecord
{
    protected static string $resource = MoleculeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
