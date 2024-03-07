<?php

namespace Database\Seeders;
 
use App\Models\Collection;
use App\Models\License;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Collection::factory()
            ->hasCitations(3)
            ->count(10)
            ->for(License::inRandomOrder()->first())
            ->create();
    }
}
