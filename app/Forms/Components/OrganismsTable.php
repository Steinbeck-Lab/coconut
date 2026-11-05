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
        $arr = explode(' ', $record_name);
        $genus = null;

        if (count($arr) > 1) {
            // Has space - use first word as genus
            $genus = $arr[0];
        } else {
            // No space - try to split CamelCase (e.g., "Micheliachampaca" -> "Michelia")
            // Look for capital letter in the middle of the string
            if (preg_match('/^([A-Z][a-z]+)([A-Z][a-z]+)/', $record_name, $matches)) {
                // CamelCase detected - use first part as genus
                $genus = $matches[1];
            } else {
                // No CamelCase - try common genus lengths (5-10 chars)
                // Most genera are 5-10 characters long
                $genus = substr($record_name, 0, min(strlen($record_name), 8));
            }
        }

        return Organism::select('id', 'name', 'iri', 'molecule_count')
            ->selectRaw('
                CASE 
                    WHEN LOWER(name) = LOWER(?) THEN 0
                    WHEN name ILIKE ? THEN 1
                    WHEN name ILIKE ? THEN 2
                    WHEN name ILIKE ? THEN 3
                    ELSE 4
                END as similarity_rank
            ', [
                $genus,                    // Exact genus match
                $genus.' %',             // Genus + space + species (exact genus)
                $genus.'%',              // Starts with genus
                '%'.$genus.'%',         // Contains genus
            ])
            ->where('molecule_count', '>', 0)
            ->where(function ($q) use ($genus, $record_name) {
                $q->where('name', '!=', $record_name)
                    ->where(function ($subQ) use ($genus, $record_name) {
                        // Flexible search patterns
                        $subQ->where('name', 'ILIKE', $genus.'%')        // Starts with genus
                            ->orWhere('name', 'ILIKE', $genus.' %')    // Genus followed by space
                            ->orWhere('name', 'ILIKE', '%'.$genus.'%'); // Contains genus

                        // Also search without the concatenated version
                        // e.g., "Apocynumcannabinum" should also find similar concatenated names
                        if (strlen($record_name) > 10 && ! str_contains($record_name, ' ')) {
                            $subQ->orWhere('name', 'ILIKE', substr($record_name, 0, 6).'%');
                        }
                    });
            })
            ->orderBy('similarity_rank')
            ->orderByDesc('molecule_count')
            ->get();
    }
}
