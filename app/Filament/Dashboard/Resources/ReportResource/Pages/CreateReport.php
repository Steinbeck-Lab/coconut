<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Filament\Dashboard\Resources\ReportResource;
use Filament\Resources\Pages\CreateRecord;
use App\Events\ReportSubmitted;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function beforeCreate(): void
    {
        if (!($this->data['collections'] || $this->data['citations']) ) {
            Notification::make()
                ->danger()
                ->title('Select at least one Collection or Citation or Molecule.')
                // ->body('Choose a plan to continue.')
                ->persistent()
                ->send();
        
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        ReportSubmitted::dispatch($this->record);
    }
}
