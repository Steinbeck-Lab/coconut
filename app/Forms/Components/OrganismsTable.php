<?php

namespace App\Forms\Components;

use App\Models\Organism;
use Filament\Forms\Components\Field;

class OrganismsTable extends Field
{
    protected string $view = 'forms.components.organisms-table';

    public static function make(string $name): static
    {
        return parent::make($name);
    }

    public function getTableData($record_name)
    {
        return Organism::select('id', 'name', urldecode('iri'), 'molecule_count')
            ->where('molecule_count', '>', 0)
            ->where(function ($q) use ($record_name) {
                $q->where('name', 'ILIKE', '%' . $record_name . '%')
                ->orWhereRaw('? ILIKE CONCAT(\'%\', name, \'%\')', [$record_name]);
            })
            ->get();
    }
}
