<?php

namespace Tests\Feature;

use App\Livewire\CollectionList;
use App\Models\Collection;
use App\Models\Molecule;
use App\Support\CollectionAnnotationScores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollectionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_page_only_lists_published_collections(): void
    {
        $published = Collection::factory()->published()->create([
            'title' => 'Published Coconut Collection',
        ]);

        Collection::factory()->create([
            'title' => 'Draft Coconut Collection',
            'status' => 'DRAFT',
        ]);

        $response = $this->get('/collections');

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee('Draft Coconut Collection');

        Livewire::test(CollectionList::class)
            ->assertViewHas('collections', function ($collections) use ($published) {
                return $collections->count() === 1
                    && $collections->first()->is($published);
            });
    }

    public function test_default_sort_is_release_date_newest_first(): void
    {
        $older = Collection::factory()->published()->create([
            'title' => 'Older Release',
            'release_date' => now()->subYear(),
        ]);

        $newer = Collection::factory()->published()->create([
            'title' => 'Newer Release',
            'release_date' => now()->subMonth(),
        ]);

        Livewire::test(CollectionList::class)
            ->assertSet('sortBy', 'release_date')
            ->assertSet('sortDir', 'desc')
            ->assertViewHas('collections', function ($collections) use ($newer, $older) {
                return $collections->count() === 2
                    && $collections->first()->is($newer)
                    && $collections->get(1)->is($older);
            });
    }

    public function test_sort_by_molecules_count_descending(): void
    {
        $fewer = Collection::factory()->published()->create([
            'title' => 'Fewer NPs',
            'molecules_count' => 10,
        ]);

        $more = Collection::factory()->published()->create([
            'title' => 'More NPs',
            'molecules_count' => 1000,
        ]);

        Livewire::test(CollectionList::class)
            ->set('sortBy', 'molecules_count')
            ->set('sortDir', 'desc')
            ->assertViewHas('collections', function ($collections) use ($more, $fewer) {
                return $collections->first()->is($more)
                    && $collections->get(1)->is($fewer);
            });
    }

    public function test_sort_by_molecules_count_ascending(): void
    {
        $fewer = Collection::factory()->published()->create([
            'title' => 'Fewer NPs',
            'molecules_count' => 10,
        ]);

        $more = Collection::factory()->published()->create([
            'title' => 'More NPs',
            'molecules_count' => 1000,
        ]);

        Livewire::test(CollectionList::class)
            ->set('sortBy', 'molecules_count')
            ->set('sortDir', 'asc')
            ->assertViewHas('collections', function ($collections) use ($fewer, $more) {
                return $collections->first()->is($fewer)
                    && $collections->get(1)->is($more);
            });
    }

    public function test_sort_by_avg_annotation_level_descending(): void
    {
        $low = Collection::factory()->published()->create(['title' => 'Low Score Collection']);
        $high = Collection::factory()->published()->create(['title' => 'High Score Collection']);

        $lowMolecule = Molecule::create([
            'active' => true,
            'is_parent' => false,
            'has_variants' => false,
            'annotation_level' => 1,
        ]);
        $highMolecule = Molecule::create([
            'active' => true,
            'is_parent' => false,
            'has_variants' => false,
            'annotation_level' => 5,
        ]);

        $low->molecules()->attach($lowMolecule);
        $high->molecules()->attach($highMolecule);

        CollectionAnnotationScores::forget();

        Livewire::test(CollectionList::class)
            ->set('sortBy', 'avg_annotation_level')
            ->set('sortDir', 'desc')
            ->assertViewHas('collections', function ($collections) use ($high, $low) {
                return $collections->count() === 2
                    && $collections->first()->is($high)
                    && $collections->get(1)->is($low);
            });
    }

    public function test_empty_state_when_search_has_no_matches(): void
    {
        Collection::factory()->published()->create([
            'title' => 'Visible Collection',
        ]);

        Livewire::test(CollectionList::class)
            ->set('query', 'nonexistent-xyz')
            ->assertSee('No collections match your search')
            ->assertSee('Clear search')
            ->assertViewHas('collections', fn ($collections) => $collections->isEmpty());

        Livewire::test(CollectionList::class)
            ->set('query', 'nonexistent-xyz')
            ->call('clearSearch')
            ->assertSet('query', '')
            ->assertSee('Visible Collection');
    }

    public function test_sort_by_avg_annotation_level_ascending(): void
    {
        $low = Collection::factory()->published()->create(['title' => 'Low Score Collection']);
        $high = Collection::factory()->published()->create(['title' => 'High Score Collection']);

        $lowMolecule = Molecule::create([
            'active' => true,
            'is_parent' => false,
            'has_variants' => false,
            'annotation_level' => 1,
        ]);
        $highMolecule = Molecule::create([
            'active' => true,
            'is_parent' => false,
            'has_variants' => false,
            'annotation_level' => 5,
        ]);

        $low->molecules()->attach($lowMolecule);
        $high->molecules()->attach($highMolecule);

        CollectionAnnotationScores::forget();

        Livewire::test(CollectionList::class)
            ->set('sortBy', 'avg_annotation_level')
            ->set('sortDir', 'asc')
            ->assertViewHas('collections', function ($collections) use ($high, $low) {
                return $collections->count() === 2
                    && $collections->first()->is($low)
                    && $collections->get(1)->is($high);
            });
    }
}
