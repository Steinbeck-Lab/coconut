<?php

namespace Tests\Feature\Rest;

use App\Models\Molecule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoleculeSearchIlikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_molecule_search_accepts_ilike_filter_for_case_insensitive_match(): void
    {
        $user = User::factory()->create();

        Molecule::create([
            'identifier' => 'CNP9999001.0',
            'name' => 'Caffeine',
            'active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/molecules/search', [
            'search' => [
                'filters' => [
                    ['field' => 'name', 'operator' => 'ilike', 'value' => '%caffeine%'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Caffeine');
    }

    public function test_molecule_search_like_filter_remains_case_sensitive(): void
    {
        $user = User::factory()->create();

        Molecule::create([
            'identifier' => 'CNP9999001.0',
            'name' => 'Caffeine',
            'active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/molecules/search', [
            'search' => [
                'filters' => [
                    ['field' => 'name', 'operator' => 'like', 'value' => '%caffeine%'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data', []);
    }

    public function test_molecule_search_rejects_unknown_filter_operator(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/molecules/search', [
            'search' => [
                'filters' => [
                    ['field' => 'name', 'operator' => 'invalid', 'value' => '%caffeine%'],
                ],
            ],
        ]);

        $response->assertUnprocessable();
    }
}
