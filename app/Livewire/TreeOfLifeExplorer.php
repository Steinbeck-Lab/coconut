<?php

namespace App\Livewire;

use App\Services\OrganismTaxonomy\OrganismTaxonomyStats;
use App\Services\OrganismTaxonomy\OrganismTaxonomyTreeBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.guest')]
class TreeOfLifeExplorer extends Component
{
    #[Url(as: 'node')]
    public ?string $selectedNodeId = null;

    #[Url(as: 'rank')]
    public string $distributionRank = 'children';

    /** @var array<string, mixed> */
    public array $tree = [];

    /** @var array<string, array<string, mixed>> */
    public array $index = [];

    public int $totalMoleculesWithOrganisms = 0;

    public int $classifiedOrganisms = 0;

    public int $totalSourceOrganisms = 0;

    public function mount(OrganismTaxonomyTreeBuilder $builder, OrganismTaxonomyStats $stats): void
    {
        $payload = Cache::flexible('tree-of-life.taxonomy', [3600, 7200], fn (): array => $builder->build());

        $this->tree = $payload['tree'];
        $this->index = $payload['index'];

        $this->totalSourceOrganisms = (int) DB::table('organisms')
            ->where('molecule_count', '>', 0)
            ->count();

        $this->classifiedOrganisms = (int) DB::table('organisms')
            ->where('molecule_count', '>', 0)
            ->whereNotNull('taxonomy')
            ->count();

        $this->totalMoleculesWithOrganisms = $stats->uniqueMoleculesWithOrganisms();
    }

    public function selectNode(?string $nodeId): void
    {
        $this->selectedNodeId = $nodeId === 'root' ? null : $nodeId;

        if ($this->selectedNodeId === null) {
            $this->distributionRank = 'children';
        }
    }

    public function setDistributionRank(string $rank): void
    {
        if (in_array($rank, ['children', 'kingdom', 'phylum', 'class', 'order', 'family', 'genus'], true)) {
            $this->distributionRank = $rank;
        }
    }

    public function render(OrganismTaxonomyTreeBuilder $builder)
    {
        $selectedNode = $builder->findNode($this->tree, $this->selectedNodeId);

        $distribution = $this->distributionRank === 'children' && ($selectedNode['children'] ?? []) !== []
            ? $builder->childDistribution($selectedNode)
            : $builder->distributionAtRank(
                $selectedNode,
                $this->distributionRank === 'children' ? 'genus' : $this->distributionRank,
            );

        $organisms = $builder->organismsUnderNode($selectedNode, 40);
        $treemapItems = $this->buildTreemapItems($distribution);

        return view('livewire.tree-of-life-explorer', [
            'selectedNode' => $selectedNode,
            'distribution' => $distribution,
            'treemapItems' => $treemapItems,
            'organisms' => $organisms,
            'breadcrumb' => $this->selectedNodeId !== null
                ? ($this->index[$this->selectedNodeId]['breadcrumb'] ?? [])
                : [],
            'classifiedPercent' => $this->totalSourceOrganisms > 0
                ? round(($this->classifiedOrganisms / $this->totalSourceOrganisms) * 100, 1)
                : 0,
        ]);
    }

    /**
     * @param  list<array{id: string, name: string, molecule_count: int, organism_count: int, rank?: string}>  $distribution
     * @return list<array{id: string, name: string, value: int, organisms: int}>
     */
    private function buildTreemapItems(array $distribution): array
    {
        return array_values(array_map(static fn (array $item): array => [
            'id' => $item['id'],
            'name' => $item['name'],
            'value' => (int) $item['molecule_count'],
            'organisms' => (int) $item['organism_count'],
        ], array_slice($distribution, 0, 30)));
    }
}
