<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Filament\Dashboard\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['organisms_changes'] = $data['suggested_changes']['organisms_changes'];
        $data['geo_locations_changes'] = $data['suggested_changes']['geo_locations_changes'];
        $data['synonyms_changes'] = $data['suggested_changes']['synonyms_changes'];
        $data['identifiers_changes'] = $data['suggested_changes']['identifiers_changes'];
        $data['citations_changes'] = $data['suggested_changes']['citations_changes'];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
