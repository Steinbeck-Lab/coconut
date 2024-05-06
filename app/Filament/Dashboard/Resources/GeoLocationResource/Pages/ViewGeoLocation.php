<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Pages;

use App\Filament\Dashboard\Resources\GeoLocationResource;
use App\Filament\Dashboard\Resources\GeoLocationResource\Widgets\GeoLocationStats;
use Filament\Resources\Pages\ViewRecord;

class ViewGeoLocation extends ViewRecord
{
    protected static string $resource = GeoLocationResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            GeoLocationStats::class,
        ];
    }
}
