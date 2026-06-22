<?php

namespace Tests\Unit;

use App\Models\Organism;
use App\Services\OrganismTaxonomy\OrganismTaxonomyTreeBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganismTaxonomyTreeBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_organisms_by_taxonomic_lineage(): void
    {
        Organism::query()->create([
            'name' => 'Aspergillus fumigatus',
            'molecule_count' => 12,
            'taxonomy' => [
                'lineage' => [
                    ['rank' => 'kingdom', 'name' => 'Fungi'],
                    ['rank' => 'phylum', 'name' => 'Ascomycota'],
                    ['rank' => 'genus', 'name' => 'Aspergillus'],
                ],
            ],
        ]);

        Organism::query()->create([
            'name' => 'Penicillium chrysogenum',
            'molecule_count' => 8,
            'taxonomy' => [
                'lineage' => [
                    ['rank' => 'kingdom', 'name' => 'Fungi'],
                    ['rank' => 'phylum', 'name' => 'Ascomycota'],
                    ['rank' => 'genus', 'name' => 'Penicillium'],
                ],
            ],
        ]);

        $builder = new OrganismTaxonomyTreeBuilder;
        $payload = $builder->build();
        $tree = $payload['tree'];

        $this->assertSame(20, $tree['molecule_count']);
        $this->assertSame(2, $tree['organism_count']);

        $fungi = collect($tree['children'])->firstWhere('name', 'Fungi');
        $this->assertNotNull($fungi);
        $this->assertSame(20, $fungi['molecule_count']);
        $this->assertSame(2, $fungi['organism_count']);

        $distribution = $builder->childDistribution($fungi);
        $ascomycota = collect($distribution)->firstWhere('name', 'Ascomycota');
        $this->assertNotNull($ascomycota);
        $this->assertSame(20, $ascomycota['molecule_count']);
    }

    public function test_it_groups_unclassified_organisms_separately(): void
    {
        Organism::query()->create([
            'name' => 'Unknown marine sponge',
            'molecule_count' => 3,
            'taxonomy' => null,
        ]);

        $builder = new OrganismTaxonomyTreeBuilder;
        $tree = $builder->build()['tree'];

        $unclassified = collect($tree['children'])->firstWhere('name', 'Unclassified sources');
        $this->assertNotNull($unclassified);
        $this->assertSame(3, $unclassified['molecule_count']);
    }

    public function test_it_finds_nodes_and_lists_organisms_under_branch(): void
    {
        $organism = Organism::query()->create([
            'name' => 'Catharanthus roseus',
            'molecule_count' => 5,
            'taxonomy' => [
                'lineage' => [
                    ['rank' => 'kingdom', 'name' => 'Plantae'],
                    ['rank' => 'family', 'name' => 'Apocynaceae'],
                ],
            ],
        ]);

        $builder = new OrganismTaxonomyTreeBuilder;
        $payload = $builder->build();

        $plantae = collect($payload['tree']['children'])->firstWhere('name', 'Plantae');
        $selected = $builder->findNode($payload['tree'], $plantae['id']);

        $organisms = $builder->organismsUnderNode($selected);
        $this->assertTrue($organisms->contains(fn (array $row): bool => $row['id'] === $organism->id));
    }
}
