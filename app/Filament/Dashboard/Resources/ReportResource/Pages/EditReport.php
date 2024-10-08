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
        if ($this->record['is_change'] == true) {
            // initiate the flags to show only the fields that need to be shown - overall changes are always from the initial suggestions
            if (array_key_exists('geo_location_changes', $this->record['suggested_changes']['overall_changes'])) {
                $data['show_geo_location_existing'] = $this->record['suggested_changes']['overall_changes']['geo_location_changes']['delete'] ? true : false;
                $data['show_geo_location_new'] = $this->record['suggested_changes']['overall_changes']['geo_location_changes']['add'] ? true : false;
            }
            if (array_key_exists('synonym_changes', $this->record['suggested_changes']['overall_changes'])) {
                $data['show_synonym_existing'] = $this->record['suggested_changes']['overall_changes']['synonym_changes']['delete'] ? true : false;
                $data['show_synonym_new'] = $this->record['suggested_changes']['overall_changes']['synonym_changes']['add'] ? true : false;
            }
            if (array_key_exists('name_change', $this->record['suggested_changes']['overall_changes'])) {
                $data['show_name_change'] = $this->record['suggested_changes']['overall_changes']['name_change'] ? true : false;
            }
            if (array_key_exists('cas_changes', $this->record['suggested_changes']['overall_changes'])) {
                $data['show_cas_existing'] = $this->record['suggested_changes']['overall_changes']['cas_changes']['delete'] ? true : false;
                $data['show_cas_new'] = $this->record['suggested_changes']['overall_changes']['cas_changes']['add'] ? true : false;
            }
            if (array_key_exists('organism_changes', $this->record['suggested_changes']['overall_changes'])) {
                $data['show_organism_existing'] = $this->record['suggested_changes']['overall_changes']['organism_changes']['delete'] ? true : false;
                $data['show_organism_new'] = $this->record['suggested_changes']['overall_changes']['organism_changes']['add'] ? true : false;
            }
            if (array_key_exists('citation_changes', $this->record['suggested_changes']['overall_changes'])) {
                $data['show_citation_existing'] = $this->record['suggested_changes']['overall_changes']['citation_changes']['delete'] ? true : false;
                $data['show_citation_new'] = $this->record['suggested_changes']['overall_changes']['citation_changes']['add'] ? true : false;
            }

            $curators_copy_changes = $this->record['suggested_changes']['curator'];
            $data['existing_geo_locations'] = $curators_copy_changes['existing_geo_locations'];
            $data['new_geo_locations'] = $curators_copy_changes['new_geo_locations'];
            $data['approve_geo_locations'] = $curators_copy_changes['approve_geo_locations'];

            $data['existing_synonyms'] = $curators_copy_changes['existing_synonyms'];
            $data['new_synonyms'] = $curators_copy_changes['new_synonyms'];
            $data['approve_synonyms'] = $curators_copy_changes['approve_synonyms'];

            $data['name'] = $curators_copy_changes['name'];
            $data['approve_name'] = $curators_copy_changes['approve_name'];

            $data['existing_cas'] = $curators_copy_changes['existing_cas'];
            $data['new_cas'] = $curators_copy_changes['new_cas'];
            $data['approve_cas'] = $curators_copy_changes['approve_cas'];

            $data['existing_organisms'] = $curators_copy_changes['existing_organisms'];
            $data['approve_existing_organisms'] = $curators_copy_changes['approve_existing_organisms'];

            $data['new_organisms'] = $curators_copy_changes['new_organisms'];

            $data['existing_citations'] = $curators_copy_changes['existing_citations'];
            $data['approve_existing_citations'] = $curators_copy_changes['approve_existing_citations'];

            $data['new_citations'] = $curators_copy_changes['new_citations'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->is_change) {
            $data = copyChangesToCuratorJSON($this->record, $data);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
