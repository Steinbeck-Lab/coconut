<?php

namespace App\Services\OrganismTaxonomy;

use App\Models\Organism;

class OrganismCurationResolver
{
    public const PATTERN_IRI_WITHOUT_TAXONOMY = 'iri_without_taxonomy';

    public const PATTERN_SPECIES_PLACEHOLDER = 'species_placeholder';

    public const PATTERN_INFRASPECIFIC = 'infraspecific';

    public const PATTERN_HYBRID_NOTATION = 'hybrid_notation';

    public const PATTERN_AUTHOR_CITATION = 'author_citation';

    public const PATTERN_CF_NOTATION = 'cf_notation';

    public const PATTERN_UNDETERMINED = 'undetermined';

    public const PATTERN_VERNACULAR = 'vernacular';

    public const PATTERN_UNCLASSIFIED = 'unclassified';

    public function __construct(
        private readonly OrganismNameNormalizer $nameNormalizer = new OrganismNameNormalizer,
        private readonly ?NcbiTaxonomyNameResolver $ncbiNameResolver = null,
    ) {}

    public function classify(Organism $organism): OrganismCurationClassification
    {
        $name = trim($organism->name);

        if ($this->isUndeterminedName($name)) {
            return $this->definition(self::PATTERN_UNDETERMINED);
        }

        if ($this->isSpeciesPlaceholder($name)) {
            return $this->definition(self::PATTERN_SPECIES_PLACEHOLDER);
        }

        if ($this->vernacularScientificName($name) !== null) {
            return $this->definition(self::PATTERN_VERNACULAR);
        }

        if ($this->isVernacularCandidate($name, $organism)) {
            return $this->definition(self::PATTERN_VERNACULAR);
        }

        if ($this->hasInfraspecificEpithet($name)) {
            return $this->definition(self::PATTERN_INFRASPECIFIC);
        }

        if ($this->hasHybridNotation($name)) {
            return $this->definition(self::PATTERN_HYBRID_NOTATION);
        }

        if ($this->hasAuthorCitation($name)) {
            return $this->definition(self::PATTERN_AUTHOR_CITATION);
        }

        if ($this->hasCfNotation($name)) {
            return $this->definition(self::PATTERN_CF_NOTATION);
        }

        if ($this->hasIriWithoutTaxonomy($organism)) {
            return $this->definition(self::PATTERN_IRI_WITHOUT_TAXONOMY);
        }

        return $this->definition(self::PATTERN_UNCLASSIFIED);
    }

    /**
     * Ordered GNF lookup candidates for an organism (most specific first).
     *
     * @return list<string>
     */
    public function lookupCandidates(Organism $organism): array
    {
        $name = trim($organism->name);
        $candidates = [];

        $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($name));

        $ncbiName = $this->ncbiResolver()->scientificNameFromIri($organism->iri);

        if ($ncbiName !== null) {
            $this->pushCandidate($candidates, $ncbiName);
        }

        $vernacular = $this->vernacularScientificName($name);

        if ($vernacular !== null) {
            $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($vernacular));
        }

        $withoutAuthors = $this->stripAuthorCitations($name);

        if ($withoutAuthors !== null) {
            $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($withoutAuthors));
        }

        $withoutHybrid = $this->stripHybridMarker($name);

        if ($withoutHybrid !== null) {
            $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($withoutHybrid));
        }

        $speciesLevel = $this->stripInfraspecificEpithet($name);

        if ($speciesLevel !== null) {
            $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($speciesLevel));
        }

        $genusFromPlaceholder = $this->genusFromSpeciesPlaceholder($name);

        if ($genusFromPlaceholder !== null) {
            $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($genusFromPlaceholder));
        }

        $cfTarget = $this->targetFromCfNotation($name);

        if ($cfTarget !== null) {
            $this->pushCandidate($candidates, $this->nameNormalizer->normalizeForLookup($cfTarget));
        }

        return $candidates;
    }

    /**
     * Lookup variants to try after the primary name has failed an exact GNF match.
     *
     * @return list<string>
     */
    public function fallbackLookupCandidates(Organism $organism): array
    {
        $candidates = $this->lookupCandidates($organism);

        if ($candidates === []) {
            return [];
        }

        array_shift($candidates);

        return $candidates;
    }

    public function isFixable(Organism $organism): bool
    {
        return $this->classify($organism)->fixable;
    }

    /**
     * @return array<string, OrganismCurationClassification>
     */
    public function patternDefinitions(): array
    {
        return [
            self::PATTERN_IRI_WITHOUT_TAXONOMY => $this->definition(self::PATTERN_IRI_WITHOUT_TAXONOMY),
            self::PATTERN_SPECIES_PLACEHOLDER => $this->definition(self::PATTERN_SPECIES_PLACEHOLDER),
            self::PATTERN_INFRASPECIFIC => $this->definition(self::PATTERN_INFRASPECIFIC),
            self::PATTERN_HYBRID_NOTATION => $this->definition(self::PATTERN_HYBRID_NOTATION),
            self::PATTERN_AUTHOR_CITATION => $this->definition(self::PATTERN_AUTHOR_CITATION),
            self::PATTERN_CF_NOTATION => $this->definition(self::PATTERN_CF_NOTATION),
            self::PATTERN_UNDETERMINED => $this->definition(self::PATTERN_UNDETERMINED),
            self::PATTERN_VERNACULAR => $this->definition(self::PATTERN_VERNACULAR),
            self::PATTERN_UNCLASSIFIED => $this->definition(self::PATTERN_UNCLASSIFIED),
        ];
    }

    /**
     * @param  array<string, mixed>  $taxonomy
     * @return array<string, mixed>
     */
    public function annotateResolvedTaxonomy(
        array $taxonomy,
        Organism $organism,
        string $resolvedLookup,
        OrganismCurationClassification $classification,
    ): array {
        if ($this->nameNormalizer->equalsForLookup($resolvedLookup, $organism->name)) {
            return $taxonomy;
        }

        return array_merge($taxonomy, [
            'curation' => [
                'pattern' => $classification->pattern,
                'pattern_label' => $classification->label,
                'original_name' => $organism->name,
                'resolved_lookup' => $resolvedLookup,
            ],
        ]);
    }

    private function definition(string $pattern): OrganismCurationClassification
    {
        return match ($pattern) {
            self::PATTERN_IRI_WITHOUT_TAXONOMY => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'IRI without taxonomy',
                description: 'Organism has a taxonomy IRI but no stored GNF profile — retry with NCBI scientific name, normalized spelling, and other curated variants.',
                fixable: true,
            ),
            self::PATTERN_SPECIES_PLACEHOLDER => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Species placeholder (sp.)',
                description: 'Unresolved species label such as "Streptomyces sp." — map at genus rank via the genus name.',
                fixable: true,
            ),
            self::PATTERN_INFRASPECIFIC => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Infraspecific rank',
                description: 'Subspecies, variety, or form epithet — retry at species level without the infraspecific suffix.',
                fixable: true,
            ),
            self::PATTERN_HYBRID_NOTATION => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Hybrid notation',
                description: 'Hybrid marker (× or x) — retry without the hybrid token.',
                fixable: true,
            ),
            self::PATTERN_AUTHOR_CITATION => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Author citation',
                description: 'Taxonomic author in parentheses — retry without author citation.',
                fixable: true,
            ),
            self::PATTERN_CF_NOTATION => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Cf. notation',
                description: 'Compare-with (cf.) label — retry the compared scientific name.',
                fixable: true,
            ),
            self::PATTERN_UNDETERMINED => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Undetermined source',
                description: 'Placeholder such as "Unknown-fungus sp." — not reliably auto-resolvable.',
                fixable: false,
            ),
            self::PATTERN_VERNACULAR => new OrganismCurationClassification(
                pattern: $pattern,
                label: 'Vernacular name',
                description: 'Common English name — map via configured vernacular dictionary or manual curation.',
                fixable: true,
            ),
            default => new OrganismCurationClassification(
                pattern: self::PATTERN_UNCLASSIFIED,
                label: 'Unclassified',
                description: 'No known curation pattern — retry with normalized spelling only.',
                fixable: true,
            ),
        };
    }

    private function hasIriWithoutTaxonomy(Organism $organism): bool
    {
        return $organism->iri !== null
            && $organism->iri !== ''
            && ! is_array($organism->taxonomy);
    }

    private function isUndeterminedName(string $name): bool
    {
        return (bool) preg_match('/^unknown[\s_-]/iu', $name);
    }

    private function isSpeciesPlaceholder(string $name): bool
    {
        return (bool) preg_match('/\bspp?\.?(?:\s+.+)?$/iu', $name);
    }

    private function hasInfraspecificEpithet(string $name): bool
    {
        return (bool) preg_match('/\b(subsp|ssp|var|f|forma)\.?\s+\S+/iu', $name);
    }

    private function hasHybridNotation(string $name): bool
    {
        return (bool) preg_match('/\s[x×]\s/u', $name);
    }

    private function hasAuthorCitation(string $name): bool
    {
        return str_contains($name, '(') && str_contains($name, ')');
    }

    private function hasCfNotation(string $name): bool
    {
        return (bool) preg_match('/\bcf\.?\s+/iu', $name);
    }

    private function isVernacularCandidate(string $name, Organism $organism): bool
    {
        if ($organism->iri !== null && $organism->iri !== '') {
            return false;
        }

        if (str_contains($name, ' ')) {
            return false;
        }

        return mb_strlen($name) >= 3 && ! preg_match('/^[A-Z][a-z]+$/u', $name);
    }

    private function vernacularScientificName(string $name): ?string
    {
        $map = config('organism_curation.vernacular_names', []);

        if (! is_array($map)) {
            return null;
        }

        $key = mb_strtolower(trim($name), 'UTF-8');

        return $map[$key] ?? null;
    }

    private function stripAuthorCitations(string $name): ?string
    {
        $stripped = trim(preg_replace('/\s*\([^)]*\)/u', '', $name) ?? '');

        return $stripped !== '' && ! $this->nameNormalizer->equalsForLookup($stripped, $name)
            ? $stripped
            : null;
    }

    private function stripHybridMarker(string $name): ?string
    {
        $stripped = trim(preg_replace('/\s+[x×]\s+/u', ' ', $name) ?? '');

        return $stripped !== '' && ! $this->nameNormalizer->equalsForLookup($stripped, $name)
            ? $stripped
            : null;
    }

    private function stripInfraspecificEpithet(string $name): ?string
    {
        $stripped = trim(preg_replace('/\s+(subsp|ssp|var|f|forma)\.?\s+.+$/iu', '', $name) ?? '');

        return $stripped !== '' && ! $this->nameNormalizer->equalsForLookup($stripped, $name)
            ? $stripped
            : null;
    }

    private function genusFromSpeciesPlaceholder(string $name): ?string
    {
        if (! preg_match('/^(.+?)\s+spp?\.?(?:\s+.*)?$/iu', $name, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function ncbiResolver(): NcbiTaxonomyNameResolver
    {
        return $this->ncbiNameResolver ?? new NcbiTaxonomyNameResolver(
            taxonomyParser: new GnfTaxonomyParser,
            nameNormalizer: $this->nameNormalizer,
        );
    }

    private function targetFromCfNotation(string $name): ?string
    {
        if (! preg_match('/\bcf\.?\s+(.+)$/iu', $name, $matches)) {
            return null;
        }

        $target = trim($matches[1]);

        return $target !== '' ? $target : null;
    }

    /**
     * @param  list<string>  $candidates
     */
    private function pushCandidate(array &$candidates, string $candidate): void
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return;
        }

        foreach ($candidates as $existing) {
            if ($this->nameNormalizer->equalsForLookup($existing, $candidate)) {
                return;
            }
        }

        $candidates[] = $candidate;
    }
}
