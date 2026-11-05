<?php

namespace App\Services\OrganismTaxonomy;

use App\Models\Organism;

class OrganismTaxonomyPresenter
{
    public function __construct(
        private readonly GnfTaxonomyParser $parser = new GnfTaxonomyParser,
    ) {}

    public function hasTaxonomy(Organism $organism): bool
    {
        return is_array($organism->taxonomy) && $organism->taxonomy !== [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forOrganism(Organism $organism): ?array
    {
        if (! $this->hasTaxonomy($organism)) {
            return null;
        }

        $taxonomy = $organism->taxonomy;
        $references = $taxonomy['references'] ?? [];

        if ($organism->iri) {
            $references = $this->parser->appendNcbiReference($references, $organism->iri);
        }

        return [
            ...$taxonomy,
            'references' => $this->uniqueReferences($references),
            'name_differs' => isset($taxonomy['canonical_name'], $organism->name)
                && strcasecmp((string) $taxonomy['canonical_name'], (string) $organism->name) !== 0,
            'match_badge' => $this->matchBadge($taxonomy['match_type'] ?? null),
            'biological_group_label' => $this->biologicalGroupLabel($taxonomy['biological_group'] ?? null),
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function uniqueReferences(array $references): array
    {
        $seen = [];
        $unique = [];

        foreach ($references as $reference) {
            if (! is_array($reference) || ! isset($reference['url'])) {
                continue;
            }

            $url = (string) $reference['url'];

            if (isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $unique[] = [
                'label' => (string) ($reference['label'] ?? 'Reference'),
                'url' => $url,
            ];
        }

        return $unique;
    }

    private function matchBadge(?string $matchType): ?array
    {
        return match ($matchType) {
            'Exact' => ['label' => 'Verified', 'tone' => 'success'],
            'Fuzzy' => ['label' => 'Fuzzy match', 'tone' => 'warning'],
            'PartialExact', 'PartialFuzzy' => ['label' => 'Partial match', 'tone' => 'warning'],
            default => null,
        };
    }

    private function biologicalGroupLabel(?string $group): ?string
    {
        if ($group === null || $group === '') {
            return null;
        }

        return match (strtolower($group)) {
            'fungi', 'fungus' => 'Fungal source',
            'plantae', 'plants', 'plant' => 'Plant source',
            'animalia', 'animals', 'animal' => 'Animal source',
            'bacteria', 'bacterium' => 'Bacterial source',
            'archaea' => 'Archaeal source',
            'chromista' => 'Chromist source',
            default => ucfirst($group).' source',
        };
    }
}
