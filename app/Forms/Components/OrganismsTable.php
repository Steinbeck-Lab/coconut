<?php

namespace App\Forms\Components;

use App\Models\Organism;
use Filament\Forms\Components\Field;

class OrganismsTable extends Field
{
    protected string $view = 'forms.components.organisms-table';

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }

    public function getTableData($record_name)
    {
        return Organism::select('id', 'name', urldecode('iri'), 'molecule_count')
            ->where('molecule_count', '>', 0)
            ->where(function ($q) use ($record_name) {
                $arr = explode(' ', $record_name);
                $sanitised_org_name = $arr[0].' '.$arr[1];
                $q->where([
                    ['name', '!=', $record_name],
                    ['name', 'ILIKE', '%'.$sanitised_org_name.'%'],
                ]);
            })
            ->get();
    }
}
