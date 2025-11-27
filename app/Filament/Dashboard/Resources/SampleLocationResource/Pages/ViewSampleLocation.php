<?php

namespace App\Filament\Dashboard\Resources\SampleLocationResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Dashboard\Resources\SampleLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSampleLocation extends ViewRecord
{
    protected static string $resource = SampleLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
