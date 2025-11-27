<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LicenseResource;
use App\Filament\Resources\LicenseResource\Widgets\LicenseOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLicenses extends ListRecords
{
    protected static string $resource = LicenseResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LicenseOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
