<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\License;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InhouseCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Collection::create([
            'title' => 'COCONUT Curated Compounds',
            'slug' => Str::slug('COCONUT Curated Compounds', '-'),
            'description' => 'A curated collection of natural products by the COCONUT curators.',
            'status' => 'PUBLISHED',
            'is_public' => true,
            'uuid' => Str::uuid(),
            'jobs_status' => 'COMPLETE',
            'identifier' => 'coconut-curated-compounds',
        ]);
    }
}
