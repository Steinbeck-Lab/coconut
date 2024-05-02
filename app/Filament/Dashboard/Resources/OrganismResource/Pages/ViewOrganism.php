<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Pages;

use App\Filament\Dashboard\Resources\OrganismResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Dashboard\Resources\OrganismResource\Widgets\OrganismStats;

class ViewOrganism extends ViewRecord
{
    protected static string $resource = OrganismResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            OrganismStats::class,
        ];
    }
}
