<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\Pages;

use App\Filament\Dashboard\Resources\GeoLocationResource;
use App\Models\Molecule;
use Filament\Resources\Pages\CreateRecord;

class CreateGeoLocation extends CreateRecord
{
    protected static string $resource = GeoLocationResource::class;

    protected function beforeCreate(): void
    {
        // $molecule = Molecule::where('identifier', $this->data['molecule_id'])->get();
        // $this->data['molecule_id'] = $molecule[0]->id;
        // dd($this->data);
        // $this->data->molecules()->attach($molecule);
    }
}
