<?php

namespace App\Services\OrganismTaxonomy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrganismTaxonomyStats
{
    public function uniqueMoleculesWithOrganisms(): int
    {
        return (int) Cache::flexible('stats.molecules.with_organisms', [172800, 259200], function (): int {
            return (int) DB::table('molecule_organism')
                ->distinct('molecule_id')
                ->count('molecule_id');
        });
    }

    public function moleculeOrganismLinks(): int
    {
        return (int) DB::table('molecule_organism')->count();
    }
}
