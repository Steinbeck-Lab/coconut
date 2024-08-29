<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Events\ReportSubmitted;
use App\Filament\Dashboard\Resources\ReportResource;
use App\Models\Citation;
use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Organism;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function afterFill(): void
    {
        $request = request();
        if ($request->type == 'change') {
            $this->data['is_change'] = true;
        }
        if ($request->has('collection_uuid')) {
            $collection = Collection::where('uuid', $request->collection_uuid)->get();
            $id = $collection[0]->id;
            array_push($this->data['collections'], $id);
            $this->data['report_type'] = 'collection';
        } elseif ($request->has('citation_id')) {
            $citation = Citation::where('id', $request->citation_id)->get();
            $id = $citation[0]->id;
            array_push($this->data['citations'], $id);
            $this->data['report_type'] = 'citation';
        } elseif ($request->has('compound_id')) {
            $this->data['mol_id_csv'] = $request->compound_id;
            $this->data['report_type'] = 'molecule';
        } elseif ($request->has('organism_id')) {
            $citation = Organism::where('id', $request->organism_id)->get();
            $id = $citation[0]->id;
            array_push($this->data['organisms'], $id);
            $this->data['report_type'] = 'organism';
        }
    }

    protected function beforeCreate(): void
    {
        if ($this->data['report_type'] == 'collection') {
            $this->data['citations'] = [];
            $this->data['mol_id_csv'] = null;
            $this->data['organisms'] = [];
        } elseif ($this->data['report_type'] == 'citation') {
            $this->data['collections'] = [];
            $this->data['mol_id_csv'] = null;
            $this->data['organisms'] = [];
        } elseif ($this->data['report_type'] == 'molecule') {
            $this->data['collections'] = [];
            $this->data['citations'] = [];
            $this->data['organisms'] = [];
        } elseif ($this->data['report_type'] == 'organism') {
            $this->data['collections'] = [];
            $this->data['citations'] = [];
            $this->data['mol_id_csv'] = null;
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! is_null($this->record->mol_id_csv)) {
            $mol_identifiers = explode(',', $this->record->mol_id_csv);
            $molecules = Molecule::whereIn('identifier', $mol_identifiers)->get();
            foreach ($molecules as $molecule) {

                $this->record->molecules()->attach($molecule);
            }
        }

        ReportSubmitted::dispatch($this->record);
    }
}
