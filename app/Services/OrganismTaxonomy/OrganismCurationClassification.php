<?php

namespace App\Services\OrganismTaxonomy;

class OrganismCurationClassification
{
    public function __construct(
        public readonly string $pattern,
        public readonly string $label,
        public readonly string $description,
        public readonly bool $fixable,
    ) {}
}
