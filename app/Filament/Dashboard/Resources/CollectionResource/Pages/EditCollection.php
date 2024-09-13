<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Pages;

use App\Filament\Dashboard\Resources\CollectionResource;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\EntriesOverview;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! $data['image']) {
            $data['image'] = $this->record->image;
        }

        return $data;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EntriesOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
