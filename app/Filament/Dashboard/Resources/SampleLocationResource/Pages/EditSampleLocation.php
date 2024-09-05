<?php

namespace App\Filament\Dashboard\Resources\SampleLocationResource\Pages;

use App\Filament\Dashboard\Resources\SampleLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSampleLocation extends EditRecord
{
    protected static string $resource = SampleLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
