<?php

namespace Tests\Unit;

use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacySchedulerVersionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_collections_in_version_migration_are_not_eligible_for_legacy_pipeline(): void
    {
        $pending = Collection::factory()->create([
            'version_migration_status' => Collection::VERSION_MIGRATION_PENDING,
            'identifier' => 'CNPC_PENDING',
            'version' => 2,
        ]);

        $normal = Collection::factory()->create([
            'version_migration_status' => null,
            'identifier' => 'CNPC_NORMAL',
            'version' => 1,
        ]);

        $eligible = Collection::query()->eligibleForLegacyPipeline()->pluck('id')->all();

        $this->assertContains($normal->id, $eligible);
        $this->assertNotContains($pending->id, $eligible);
    }
}
