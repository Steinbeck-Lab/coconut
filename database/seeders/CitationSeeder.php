<?php

namespace Database\Seeders;

use App\Models\Citation;
use Illuminate\Database\Seeder;

class CitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Citation::factory()
            ->count(10)
            ->create();
    }
}
