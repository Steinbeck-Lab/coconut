<?php

namespace App\Services\OrganismTaxonomy;

class GnfMatchGate
{
    public const MATCH_TYPE_EXACT = 'Exact';

    private const ALLOWED_NON_EXACT_MATCH_TYPES = [
        'Fuzzy',
        'PartialExact',
        'PartialFuzzy',
    ];

    public function __construct(
        private readonly bool $requireExactMatch = true,
        private readonly OrganismNameNormalizer $nameNormalizer = new OrganismNameNormalizer,
    ) {}

    public static function fromConfig(): self
    {
        return new self((bool) config('services.organism_taxonomy.require_exact_gnf_match', true));
    }

    public function requiresExactMatch(): bool
    {
        return $this->requireExactMatch;
    }

    public function acceptsMatchType(?string $matchType): bool
    {
        if ($matchType === null || $matchType === '' || $matchType === 'NoMatch') {
            return false;
        }

        if (! $this->requireExactMatch) {
            return $matchType === self::MATCH_TYPE_EXACT
                || in_array($matchType, self::ALLOWED_NON_EXACT_MATCH_TYPES, true);
        }

        return $matchType === self::MATCH_TYPE_EXACT;
    }

    /**
     * @param  array<string, mixed>|null  $profile
     */
    public function acceptsTaxonomyProfile(?array $profile, string $lookupName): bool
    {
        if ($profile === null) {
            return false;
        }

        if (! $this->acceptsMatchType($profile['match_type'] ?? null)) {
            return false;
        }

        if (isset($profile['edit_distance']) && (int) $profile['edit_distance'] !== 0) {
            return false;
        }

        $matched = $profile['matched_name'] ?? null;

        if (is_string($matched) && $this->nameNormalizer->equalsForLookup($lookupName, $matched)) {
            return true;
        }

        $canonical = $profile['canonical_name'] ?? null;

        return is_string($canonical)
            && $this->nameNormalizer->equalsForLookup($lookupName, $canonical);
    }
}
