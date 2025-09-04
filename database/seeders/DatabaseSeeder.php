<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ShieldSeeder::class);
        $this->call(LicenseSeeder::class);
        $this->call(UsersSeeder::class);
        // $this->call(CitationSeeder::class);
        // $this->call(CollectionSeeder::class);
    }
}
