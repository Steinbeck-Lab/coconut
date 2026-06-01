<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $title = $this->faker->sentence($nbWords = 4);
        $slug = Str::slug($title, '-');

        return [
            'title' => $title,
            'slug' => $slug,
            'doi' => $this->faker->optional()->url(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['DRAFT']),
            'release_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'comments' => $this->faker->optional()->paragraph(),
            'url' => $this->faker->url(),
            'identifier' => $this->faker->optional()->sentence(),
            'license_id' => null,
            'photo' => $this->faker->optional()->imageUrl(),
            'owner_id' => null,
            'uuid' => $this->faker->uuid(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PUBLISHED',
        ]);
    }
}
