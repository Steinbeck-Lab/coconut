<?php

namespace Tests\Unit;

use App\Models\Molecule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UpdateParentVariantsCountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_clears_placeholder_flag_for_parents_without_variants(): void
    {
        $parent = Molecule::create([
            'identifier' => 'CNP9999100.0',
            'name' => 'Mirabilin B',
            'is_parent' => true,
            'has_variants' => false,
            'is_placeholder' => true,
            'variants_count' => 1,
            'active' => true,
        ]);

        Artisan::call('coconut:update-parent-variants-counts');

        $parent->refresh();

        $this->assertFalse($parent->has_variants);
        $this->assertFalse($parent->is_placeholder);
        $this->assertSame(0, $parent->variants_count);
    }

    public function test_keeps_placeholder_flag_for_parents_with_variants(): void
    {
        $parent = Molecule::create([
            'identifier' => 'CNP9999101.0',
            'is_parent' => true,
            'has_variants' => false,
            'is_placeholder' => true,
            'variants_count' => 0,
            'active' => true,
        ]);

        Molecule::create([
            'identifier' => 'CNP9999101.1',
            'parent_id' => $parent->id,
            'has_stereo' => true,
            'is_placeholder' => false,
            'active' => true,
        ]);

        Artisan::call('coconut:update-parent-variants-counts');

        $parent->refresh();

        $this->assertTrue($parent->has_variants);
        $this->assertTrue($parent->is_placeholder);
        $this->assertSame(1, $parent->variants_count);
    }
}
