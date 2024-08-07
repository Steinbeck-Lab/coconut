<?php

namespace App\Filament\Dashboard\Resources\EntryResource\Pages;

use App\Filament\Dashboard\Resources\EntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEntry extends EditRecord
{
    protected static string $resource = EntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
