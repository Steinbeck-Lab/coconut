<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Pages;

use App\Filament\Dashboard\Resources\MoleculeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Dashboard\Resources\MoleculeResource\Widgets\MoleculeStats;

class ViewMolecule extends ViewRecord
{
    protected static string $resource = MoleculeResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MoleculeStats::class,
        ];
    }
}
