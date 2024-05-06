<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Pages;

use App\Filament\Dashboard\Resources\OrganismResource;
use App\Filament\Dashboard\Resources\OrganismResource\Widgets\OrganismStats;
use Filament\Resources\Pages\ViewRecord;

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
