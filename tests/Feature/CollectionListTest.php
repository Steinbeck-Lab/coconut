<?php

namespace Tests\Feature;

use App\Livewire\CollectionList;
use App\Models\Collection;
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
}
