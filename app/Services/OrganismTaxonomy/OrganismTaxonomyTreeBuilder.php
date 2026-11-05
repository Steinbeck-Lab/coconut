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

        /** @var array<string, mixed> $index */
        $index = [];
        $index = $this->indexNode($tree, [], $index);

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
        /** @var array<string, mixed> $buckets */
        $buckets = [];

        $buckets = $this->collectRankBuckets($node, $rank, $buckets);

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
        $nodeId = (string) ($node['id'] ?? 'root');

        if ($nodeId === 'root') {
            return Organism::query()
                ->where('molecule_count', '>', 0)
                ->orderByDesc('molecule_count')
                ->limit($limit)
                ->get(['id', 'name', 'molecule_count'])
                ->map(static fn (Organism $organism): array => [
                    'id' => $organism->id,
                    'name' => $organism->name,
                    'molecule_count' => (int) $organism->molecule_count,
                ]);
        }

        $matches = collect();

        foreach (Organism::query()
            ->where('molecule_count', '>', 0)
            ->select(['id', 'name', 'molecule_count', 'taxonomy'])
            ->orderByDesc('molecule_count')
            ->cursor() as $organism) {
            if (! $this->organismBelongsToNode($organism, $nodeId)) {
                continue;
            }

            $matches->push([
                'id' => $organism->id,
                'name' => $organism->name,
                'molecule_count' => (int) $organism->molecule_count,
            ]);

            if ($matches->count() >= $limit) {
                break;
            }
        }

        return $matches->values();
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function insertOrganism(array &$node, Organism $organism): void
    {
        $lineage = $organism->taxonomy['lineage'] ?? null;

        if (! is_array($lineage) || $lineage === []) {
            $this->insertAlongPath($node, [
                ['rank' => 'group', 'name' => 'Unclassified sources'],
            ], $organism);

            return;
        }

        $path = $this->normalizeLineage($lineage);

        if ($path === []) {
            $path = [
                ['rank' => 'group', 'name' => 'Unclassified sources'],
            ];
        }

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
     * @param  array<string, mixed>  $index
     * @return array<string, mixed>
     */
    private function indexNode(array $node, array $breadcrumb, array $index): array
    {
        $index[$node['id']] = [
            'id' => $node['id'],
            'breadcrumb' => $breadcrumb,
        ];

        foreach ($node['children'] as $child) {
            $index = $this->indexNode(
                $child,
                [...$breadcrumb, ['rank' => $child['rank'], 'name' => $child['name']]],
                $index,
            );
        }

        return $index;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $buckets
     * @return array<string, mixed>
     */
    private function collectRankBuckets(array $node, string $targetRank, array $buckets): array
    {
        $currentRank = strtolower((string) $node['rank']);

        if ($node['id'] !== 'root' && $currentRank === $targetRank) {
            $buckets[$node['id']] = [
                'id' => $node['id'],
                'name' => $node['name'],
                'molecule_count' => (int) $node['molecule_count'],
                'organism_count' => (int) $node['organism_count'],
            ];

            return $buckets;
        }

        foreach ($node['children'] as $child) {
            $buckets = $this->collectRankBuckets($child, $targetRank, $buckets);
        }

        return $buckets;
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

    private function organismBelongsToNode(Organism $organism, string $nodeId): bool
    {
        return in_array($nodeId, $this->nodeIdsForOrganism($organism), true);
    }

    /**
     * @return list<string>
     */
    private function nodeIdsForOrganism(Organism $organism): array
    {
        $ids = ['root'];
        $parentId = 'root';

        $lineage = $organism->taxonomy['lineage'] ?? null;

        if (! is_array($lineage) || $lineage === []) {
            $path = [
                ['rank' => 'group', 'name' => 'Unclassified sources'],
            ];
        } else {
            $path = $this->normalizeLineage($lineage);

            if ($path === []) {
                $path = [
                    ['rank' => 'group', 'name' => 'Unclassified sources'],
                ];
            }
        }

        foreach ($path as $segment) {
            $parentId = $this->segmentId($parentId, $segment['rank'], $segment['name']);
            $ids[] = $parentId;
        }

        return $ids;
    }
}
