<?php

namespace App\Services\OrganismTaxonomy;

use App\Models\Organism;

class OrganismTaxonomyApplier
{
    public function apply(Organism $organism, OrganismTaxonomyMappingResult $result): void
    {
        if ($result->iri !== null && $result->iri !== '') {
            $organism->iri = $result->iri;
        }

        if ($result->rank !== null && $result->rank !== '') {
            $organism->rank = $result->rank;
        }

        if ($result->taxonomy !== null) {
            $organism->taxonomy = $result->taxonomy;
            $organism->taxonomy_fetched_at = now();
        }
    }

    public function applyTaxonomyOnly(Organism $organism, array $taxonomy): void
    {
        $organism->taxonomy = $taxonomy;
        $organism->taxonomy_fetched_at = now();
    }

    public function clearTaxonomy(Organism $organism): void
    {
        $organism->taxonomy = null;
        $organism->taxonomy_fetched_at = null;
    }
}
