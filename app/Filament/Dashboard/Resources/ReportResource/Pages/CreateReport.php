<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Events\ReportSubmitted;
use App\Filament\Dashboard\Resources\ReportResource;
use App\Models\Citation;
use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Organism;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected $molecule;

    public function getTitle(): string
    {
        $title = 'Create Report';
        request()->type == 'change' ? $title = 'Request Changes' : $title = 'Report ';
        if (request()->has('compound_id')) {
            $title = $title.' - '.$this->molecule->name.' ('.$this->molecule->identifier.')';
        }

        return __($title);
    }

    protected function beforeFill(): void
    {
        if (request()->has('compound_id')) {
            $this->molecule = Molecule::where('identifier', request()->compound_id)->first();
        }
    }

    protected function afterFill(): void
    {
        $request = request();
        $this->data['compound_id'] = $request->compound_id;
        if ($request->type == 'change') {
            $this->data['is_change'] = true;
            $this->data['existing_geo_locations'] = $this->molecule->geo_locations->pluck('name')->toArray();
            $this->data['existing_synonyms'] = $this->molecule->synonyms;
            $this->data['existing_cas'] = array_values($this->molecule->cas);
            $this->data['existing_organisms'] = $this->molecule->organisms->pluck('name')->toArray();
            $this->data['existing_citations'] = $this->molecule->citations->where('title', '!=', null)->pluck('title')->toArray();
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
        $data['status'] = 'submitted';

        if ($data['is_change'] == true) {
            $suggested_changes = [];
            $suggested_changes['existing_geo_locations'] = $data['existing_geo_locations'];
            $suggested_changes['new_geo_locations'] = $data['new_geo_locations'];
            $suggested_changes['approve_geo_locations'] = false;

            $suggested_changes['existing_synonyms'] = $data['existing_synonyms'];
            $suggested_changes['new_synonyms'] = $data['new_synonyms'];
            $suggested_changes['approve_synonyms'] = false;

            $suggested_changes['name'] = $data['name'];
            $suggested_changes['approve_name'] = false;

            $suggested_changes['existing_cas'] = $data['existing_cas'];
            $suggested_changes['new_cas'] = $data['new_cas'];
            $suggested_changes['approve_cas'] = false;

            $suggested_changes['existing_organisms'] = $data['existing_organisms'];
            $suggested_changes['approve_existing_organisms'] = false;

            $suggested_changes['new_organisms'] = $data['new_organisms'];

            $suggested_changes['existing_citations'] = $data['existing_citations'];
            $suggested_changes['approve_existing_citations'] = false;

            $suggested_changes['new_citations'] = $data['new_citations'];

            // seperate copy for Curators
            $suggested_changes['curator'] = $suggested_changes;

            // Overall Changes suggested
            $suggested_changes['overall_changes'] = getOverallChanges($data);

            $data['suggested_changes'] = $suggested_changes;
        }

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

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->submit(null)
            ->form(function () {
                return getChangesToDisplayModal($this->data);
            })
            ->modalHidden(function () {
                return ! $this->data['is_change'];
            })
            ->action(function () {
                $this->closeActionModal();
                $this->create();
            });
    }
}
