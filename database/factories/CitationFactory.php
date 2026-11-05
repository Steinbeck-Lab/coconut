<?php

namespace Database\Factories;

use App\Models\Citation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citation>
 */
class CitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(8), '.');
        $journalTitle = $this->faker->randomElement([
            'J Nat Prod',
            'Phytochemistry',
            'Mar Drugs',
            'Molecules',
            'Nat Prod Rep',
            'J Ethnopharmacol',
        ]);
        $pubYear = $this->faker->numberBetween(1990, (int) date('Y'));
        $volume = $this->faker->numberBetween(1, 120);
        $issue = $this->faker->numberBetween(1, 12);
        $firstPage = $this->faker->numberBetween(1, 900);
        $pageInfo = $firstPage.'-'.($firstPage + $this->faker->numberBetween(5, 30));

        $authors = collect(range(1, $this->faker->numberBetween(1, 5)))
            ->map(fn () => $this->faker->lastName().' '.strtoupper($this->faker->randomLetter()))
            ->implode(', ');

        return [
            'doi' => '10.'.$this->faker->numberBetween(1000, 9999).'/'.$this->faker->unique()->bothify('???.####.#####'),
            'title' => $title,
            'authors' => $authors,
            'citation_text' => "$journalTitle. $title. $pubYear; $volume($issue): $pageInfo",
        ];
    }
}
