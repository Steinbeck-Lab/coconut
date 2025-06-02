<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Pages;

use App\Filament\Dashboard\Resources\CollectionResource;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\EntriesOverview;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
            \Filament\Actions\Action::make('Publish Molecules')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('This will make all the new submitted molecules in this collection public. Are you sure you\'d like to proceed? This cannot be undone.')
                ->action(function () {
                    Artisan::call('coconut:publish-molecules-auto', [
                        'collection_id' => $this->record->id,
                        '--trigger' => true,
                    ]);
                })
                ->visible(function () {
                    $pendingProcessingCount = $this->record->entries()->where('status', 'SUBMITTED')->orWhere('status', 'PASSED')->count();
                    // $pendingCount = $this->record->entries()->where('status', 'PASSED')->count();
                    // $pendingProcessing = $submittedCount > 0 || $pendingCount > 0;

                    // Condition 2: Check if there are any molecules with status 'DRAFT' but null identifier
                    $moleculesStillUnderProcess = $this->record->molecules()->where('status', 'DRAFT')->whereNull('identifier')->exists();

                    $moleculesToPublish = $this->record->molecules()->where('status', 'DRAFT')->whereNotNull('identifier')->exists();

                    Log::info($pendingProcessingCount, [
                        'moleculesStillUnderProcess' => $moleculesStillUnderProcess,
                        'moleculesToPublish' => $moleculesToPublish,
                        'canUpdate' => auth()->user()->can('update', $this->record),
                    ]);

                    // Action is visible only if both conditions are false
                    return $pendingProcessingCount == 0 && $moleculesStillUnderProcess == 0 && $moleculesToPublish && auth()->user()->can('update', $this->record);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
