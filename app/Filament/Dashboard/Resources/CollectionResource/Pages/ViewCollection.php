<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Pages;

use App\Filament\Dashboard\Resources\CollectionResource;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\CollectionStats;
use App\Models\Collection;
use App\Services\CollectionVersioning\CollectionVersionCreator;
use App\Services\CollectionVersioning\CollectionVersionImporter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCollection extends ViewRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CollectionStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createNewVersion')
                ->label('Create new version')
                ->icon('heroicon-o-document-duplicate')
                ->visible(fn (Collection $record) => $record->is_latest
                    && $record->status === 'PUBLISHED'
                    && ! $record->isVersionMigrationActive())
                ->requiresConfirmation()
                ->modalHeading('Create new collection version')
                ->modalDescription('This clones metadata into a new DRAFT version with the same CNPC identifier. Import CSV entries, then use Preview and Process new version.')
                ->action(function (Collection $record, CollectionVersionCreator $creator) {
                    $new = $creator->createFrom($record);
                    Notification::make()
                        ->title('New version created')
                        ->body("Version {$new->version} is ready for CSV import.")
                        ->success()
                        ->send();

                    $this->redirect(CollectionResource::getUrl('view', ['record' => $new]));
                }),
            Action::make('previewVersionMigration')
                ->label('Preview migration')
                ->icon('heroicon-o-eye')
                ->visible(fn (Collection $record) => $record->version_migration_status === Collection::VERSION_MIGRATION_PENDING)
                ->action(function (Collection $record, CollectionVersionImporter $importer) {
                    try {
                        $preview = $importer->preview($record);
                        Notification::make()
                            ->title('Migration preview')
                            ->body(sprintf(
                                'Dropped: %d | Retained: %d | New: %d | Revoke candidates: %d',
                                $preview['old_only_count'],
                                $preview['retained_count'],
                                $preview['new_only_count'],
                                $preview['revoke_candidate_count'],
                            ))
                            ->info()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Preview failed')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('processNewVersion')
                ->label('Process new version')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (Collection $record) => in_array($record->version_migration_status, [
                    Collection::VERSION_MIGRATION_PENDING,
                    Collection::VERSION_MIGRATION_PROCESSING,
                ], true))
                ->requiresConfirmation()
                ->modalHeading('Process collection version migration')
                ->modalDescription('This validates entries, diffs by standardized SMILES, migrates live data, revokes dropped exclusive molecules, and archives the previous version.')
                ->action(function (Collection $record, CollectionVersionImporter $importer) {
                    try {
                        $result = $importer->import($record);
                        Notification::make()
                            ->title('Version migration completed')
                            ->body(sprintf(
                                'Revoked: %d | Retained: %d | New: %d',
                                $result['revoked'],
                                $result['retained'],
                                $result['new_only'],
                            ))
                            ->success()
                            ->send();
                        $this->redirect(CollectionResource::getUrl('view', ['record' => $record->fresh()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Migration failed')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
