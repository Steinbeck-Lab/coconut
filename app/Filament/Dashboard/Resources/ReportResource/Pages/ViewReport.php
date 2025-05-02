<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Filament\Dashboard\Resources\ReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($data['report_category'] === 'new_molecule') {
            $molecule_data = $data['suggested_changes']['new_molecule_data'];
            $data = array_merge($data, [
                'canonical_smiles' => $molecule_data['canonical_smiles'],
                'reference_id' => $molecule_data['reference_id'],
                'name' => $molecule_data['name'],
                'link' => $molecule_data['link'] ?? null,
                'mol_filename' => $molecule_data['mol_filename'] ?? null,
                'structural_comments' => $molecule_data['structural_comments'] ?? null,
                'references' => $molecule_data['references'] ?? [],
            ]);
        } elseif ($data['report_category'] === 'change') {
            $curators_copy_changes = $data['suggested_changes']['curator'];
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
}
