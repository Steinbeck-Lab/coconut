<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Pages;

use App\Filament\Dashboard\Resources\CollectionResource;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\EntriesOverview;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;

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
            \Filament\Actions\Action::make('Activate all molecules')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('coconut::publish-molecules-auto', [
                        'collection_id' => $this->record->id,
                    ]);
                })
                ->hidden(function () {
                    if (auth()->user()->cannot('update', $this->record)) {
                        return true;
                    }

                    $hasUnsubmittedEntries = $this->record->entries()
                        ->where(function ($query) {
                            $query->whereNull('molecule_id')
                                ->orWhere('status', 'SUBMITTED');
                        })
                        ->count() > 0;

                    $hasDraftMoleculesWithoutIdentifier = $this->record->molecules()
                        ->where('status', 'DRAFT')
                        ->whereNull('identifier')
                        ->count() > 0;

                    if ($hasUnsubmittedEntries || $hasDraftMoleculesWithoutIdentifier) {
                        return true;
                    }

                    return true;
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
