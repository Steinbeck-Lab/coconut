<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Pages;

use App\Filament\Dashboard\Resources\CollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\CollectionStats;

class ViewCollection extends ViewRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CollectionStats::class,
        ];
    }
}
