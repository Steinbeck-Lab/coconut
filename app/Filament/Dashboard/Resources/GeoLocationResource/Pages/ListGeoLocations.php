<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Dashboard\Resources\GeoLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeoLocations extends ListRecords
{
    protected static string $resource = GeoLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
