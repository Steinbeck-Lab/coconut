<?php

namespace App\Livewire;

use App\Services\OrganismTaxonomy\OrganismTaxonomyStats;
use App\Services\OrganismTaxonomy\OrganismTaxonomyTreeBuilder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class TreeOfLifeTeaser extends Component
{
    public function placeholder(): string
    {
        return <<<'HTML'
            <div class="bg-white py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="h-48 animate-pulse rounded-2xl bg-gray-100"></div>
                </div>
            </div>
        HTML;
    }

    public function render(OrganismTaxonomyTreeBuilder $builder, OrganismTaxonomyStats $stats)
    {
        $payload = Cache::flexible('tree-of-life.taxonomy', [3600, 7200], fn (): array => $builder->build());
        $kingdoms = $builder->childDistribution($payload['tree']);
        $totalMolecules = $stats->uniqueMoleculesWithOrganisms();
        $maxCount = max(1, collect($kingdoms)->max('molecule_count') ?? 1);

        return view('livewire.tree-of-life-teaser', [
            'kingdoms' => array_slice($kingdoms, 0, 8),
            'totalMolecules' => $totalMolecules,
            'maxCount' => $maxCount,
        ]);
    }
}
