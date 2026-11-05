<?php

namespace App\Services\OrganismTaxonomy;

final class OrganismTaxonomyMappingResult
{
    /**
     * @param  array<string, mixed>|null  $taxonomy
     */
    public function __construct(
        public readonly ?string $iri,
        public readonly ?string $rank,
        public readonly string $source = 'none',
        public readonly ?string $matchType = null,
        public readonly ?string $lookupName = null,
        public readonly ?array $taxonomy = null,
        public readonly ?string $canonicalName = null,
    ) {}

    public function isMapped(): bool
    {
        return $this->iri !== null && $this->iri !== '';
    }

    public static function unmapped(string $lookupName): self
    {
        return new self(null, null, 'none', null, $lookupName);
    }
}
