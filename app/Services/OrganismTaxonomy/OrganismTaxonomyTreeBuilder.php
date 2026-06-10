<?php

namespace App\Services\OrganismTaxonomy;

use App\Models\Organism;
use Illuminate\Support\Collection;

class OrganismTaxonomyTreeBuilder
{
    private const DISPLAY_RANKS = [
        'kingdom',
        'phylum',
        'class',
        'order',
        'family',
        'genus',
        'species',
    ];

    /**
     * @return array{tree: array<string, mixed>, index: array<string, array<string, mixed>>}
     */
    public function build(): array
    {
        $tree = $this->emptyNode('root', 'life', 'All life');

        Organism::query()
            ->where('molecule_count', '>', 0)
            ->select(['id', 'name', 'molecule_count', 'taxonomy'])
            ->chunkById(500, function ($organisms) use (&$tree): void {
                foreach ($organisms as $organism) {
                    $this->insertOrganism($tree, $organism);
                }
            });

        $this->sortChildren($tree);

        $index = [];
        $this->indexNode($tree, [], $index);

        return [
            'tree' => $tree,
            'index' => $index,
        ];
    }

    /**
     * @param  array<string, mixed>  $tree
     */
    public function findNode(array $tree, ?string $nodeId): array
    {
        if ($nodeId === null || $nodeId === '' || $nodeId === 'root') {
            return $tree;
        }

        return $this->findNodeRecursive($tree, $nodeId) ?? $tree;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array{name: string, molecule_count: int, organism_count: int, id: string, rank: string}>
     */
    public function childDistribution(array $node): array
    {
        $children = $node['children'] ?? [];

        return array_values(array_map(static fn (array $child): array => [
            'id' => $child['id'],
            'name' => $child['name'],
            'rank' => $child['rank'],
            'molecule_count' => (int) $child['molecule_count'],
            'organism_count' => (int) $child['organism_count'],
        ], $children));
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array{name: string, molecule_count: int, organism_count: int, id: string}>
     */
    public function distributionAtRank(array $node, string $rank): array
    {
        $rank = strtolower($rank);
        $buckets = [];

        $this->collectRankBuckets($node, $rank, $buckets);

        $distribution = [];

        foreach ($buckets as $bucket) {
            $distribution[] = [
                'id' => $bucket['id'],
                'name' => $bucket['name'],
                'rank' => $rank,
                'molecule_count' => $bucket['molecule_count'],
                'organism_count' => $bucket['organism_count'],
            ];
        }

        usort($distribution, fn (array $a, array $b): int => $b['molecule_count'] <=> $a['molecule_count']);

        return $distribution;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return Collection<int, array{id: int, name: string, molecule_count: int}>
     */
    public function organismsUnderNode(array $node, int $limit = 50): Collection
    {
        $organisms = collect();

        $this->collectOrganisms($node, $organisms);

        return $organisms
            ->sortByDesc('molecule_count')
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function insertOrganism(array &$node, Organism $organism): void
    {
        $lineage = $organism->taxonomy['lineage'] ?? null;

        if (! is_array($lineage) || $lineage === []) {
            $path = [
                ['rank' => 'group', 'name' => 'Unclassified sources'],
                ['rank' => 'source', 'name' => $organism->name],
            ];
            $this->insertAlongPath($node, $path, $organism);

            return;
        }

        $path = $this->normalizeLineage($lineage);

        if ($path === []) {
            $path = [
                ['rank' => 'group', 'name' => 'Unclassified sources'],
            ];
        }

        $path[] = ['rank' => 'source', 'name' => $organism->name];
        $this->insertAlongPath($node, $path, $organism);
    }

    /**
     * @param  list<array{rank: string, name: string, id?: string|null}>  $lineage
     * @return list<array{rank: string, name: string}>
     */
    private function normalizeLineage(array $lineage): array
    {
        $path = [];

        foreach ($lineage as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rank = strtolower((string) ($item['rank'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));

            if ($name === '' || in_array($rank, ['domain', 'superkingdom', 'other'], true)) {
                continue;
            }

            if (! in_array($rank, self::DISPLAY_RANKS, true) && $rank !== 'subkingdom') {
                continue;
            }

            $path[] = [
                'rank' => $rank,
                'name' => $name,
            ];
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<array{rank: string, name: string}>  $path
     */
    private function insertAlongPath(array &$node, array $path, Organism $organism): void
    {
        $node['molecule_count'] = (int) $node['molecule_count'] + (int) $organism->molecule_count;
        $node['organism_count'] = (int) $node['organism_count'] + 1;

        if ($path === []) {
            $node['organisms'][] = [
                'id' => $organism->id,
                'name' => $organism->name,
                'molecule_count' => (int) $organism->molecule_count,
            ];

            return;
        }

        $segment = array_shift($path);
        $childId = $this->segmentId($node['id'], $segment['rank'], $segment['name']);
        $child = &$this->findOrCreateChild($node, $childId, $segment['rank'], $segment['name']);
        $this->insertAlongPath($child, $path, $organism);
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function &findOrCreateChild(array &$node, string $childId, string $rank, string $name): array
    {
        foreach ($node['children'] as &$child) {
            if ($child['id'] === $childId) {
                return $child;
            }
        }

        $node['children'][] = $this->emptyNode($childId, $rank, $name);

        return $node['children'][array_key_last($node['children'])];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyNode(string $id, string $rank, string $name): array
    {
        return [
            'id' => $id,
            'rank' => $rank,
            'name' => $name,
            'molecule_count' => 0,
            'organism_count' => 0,
            'organisms' => [],
            'children' => [],
        ];
    }

    private function segmentId(string $parentId, string $rank, string $name): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name);

        return $parentId === 'root'
            ? $rank.':'.$slug
            : $parentId.'/'.$rank.':'.$slug;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function sortChildren(array &$node): void
    {
        usort($node['children'], fn (array $a, array $b): int => $b['molecule_count'] <=> $a['molecule_count']);

        foreach ($node['children'] as &$child) {
            $this->sortChildren($child);
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<array{rank: string, name: string}>  $breadcrumb
     * @param  array<string, array<string, mixed>>  $index
     */
    private function indexNode(array $node, array $breadcrumb, array &$index): void
    {
        $entry = [
            'id' => $node['id'],
            'rank' => $node['rank'],
            'name' => $node['name'],
            'molecule_count' => $node['molecule_count'],
            'organism_count' => $node['organism_count'],
            'breadcrumb' => $breadcrumb,
            'children' => array_map(static fn (array $child): array => [
                'id' => $child['id'],
                'name' => $child['name'],
                'rank' => $child['rank'],
                'molecule_count' => $child['molecule_count'],
                'organism_count' => $child['organism_count'],
            ], $node['children']),
            'organisms' => $node['organisms'],
        ];

        $index[$node['id']] = $entry;

        foreach ($node['children'] as $child) {
            $this->indexNode(
                $child,
                [...$breadcrumb, ['rank' => $child['rank'], 'name' => $child['name']]],
                $index,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, array{molecule_count: int, organism_count: int, id: string, name: string}>  $buckets
     */
    private function collectRankBuckets(array $node, string $targetRank, array &$buckets): void
    {
        $currentRank = strtolower((string) $node['rank']);

        if ($node['id'] !== 'root' && $currentRank === $targetRank) {
            $buckets[$node['id']] = [
                'id' => $node['id'],
                'name' => $node['name'],
                'molecule_count' => (int) $node['molecule_count'],
                'organism_count' => (int) $node['organism_count'],
            ];

            return;
        }

        foreach ($node['children'] as $child) {
            $this->collectRankBuckets($child, $targetRank, $buckets);
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    private function findNodeRecursive(array $node, string $nodeId): ?array
    {
        if ($node['id'] === $nodeId) {
            return $node;
        }

        foreach ($node['children'] as $child) {
            $found = $this->findNodeRecursive($child, $nodeId);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  Collection<int, array{id: int, name: string, molecule_count: int}>  $organisms
     */
    private function collectOrganisms(array $node, Collection $organisms): void
    {
        foreach ($node['organisms'] as $organism) {
            $organisms->push($organism);
        }

        foreach ($node['children'] as $child) {
            $this->collectOrganisms($child, $organisms);
        }
    }
}
