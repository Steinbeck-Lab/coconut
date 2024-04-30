<?php

namespace App\Filament\Dashboard\Resources\ReportResource\Pages;

use App\Events\ReportSubmitted;
use App\Filament\Dashboard\Resources\ReportResource;
use App\Models\Citation;
use App\Models\Collection;
use App\Models\Molecule;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function afterFill(): void
    {
        $request = request();
        if ($request->has('collection_uuid')) {
            $collection = Collection::where('uuid', $request->collection_uuid)->get();
            $id = $collection[0]->id;
            array_push($this->data['collections'], $id);
        } elseif ($request->has('citation_id')) {
            $citation = Citation::where('id', $request->citation_id)->get();
            $id = $citation[0]->id;
            array_push($this->data['citations'], $id);
        } elseif ($request->has('compound_id')) {
            $this->data['mol_id_csv'] = $request->compound_id;
        }
    }

    protected function beforeCreate(): void
    {
        if ($this->data['choice'] = 'collection') {
            $this->data['citations'] = [];
            $this->data['molecules'] = null;
        } elseif ($this->data['choice'] = 'citation') {
            $this->data['collections'] = [];
            $this->data['molecules'] = null;
        } elseif ($this->data['choice'] = 'molecule') {
            $this->data['collections'] = [];
            $this->data['citations'] = [];
        }

        if (! ($this->data['collections'] || $this->data['citations'] || $this->data['molecules'])) {
            Notification::make()
                ->danger()
                ->title('Select at least one Collection or Citation or Molecule.')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

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
