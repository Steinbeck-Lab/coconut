<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InhouseCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $title = 'COCONUT Community NPs';

        // Check if the collection already exists
        $existingCollection = Collection::where('title', $title)->first();
        if ($existingCollection) {
            // If it exists, you can choose to update it or skip
            // $existingCollection->update([...]);
            $this->command->info("Collection '{$title}' already exists. Skipping creation.");

            return; // Skip creating a new collection
        }

        Collection::create([
            'title' => $title,
            'slug' => Str::slug($title, '-'),
            'description' => 'Collection of natural products (NPs) submitted by the community to the COCONUT database.',
            'status' => 'PUBLISHED',
            'is_public' => true,
            'uuid' => Str::uuid(),
            'jobs_status' => 'COMPLETE',
            'identifier' => Str::slug($title, '-'),
        ]);
    }
}
