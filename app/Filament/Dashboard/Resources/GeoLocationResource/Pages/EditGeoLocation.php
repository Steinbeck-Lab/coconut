<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Pages;

use App\Filament\Dashboard\Resources\GeoLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeoLocation extends EditRecord
{
    protected static string $resource = GeoLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
