<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Citation>
 */
class CitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $keywords = [
            'Herbal medicine',
            'Secondary metabolites',
            'Natural product chemistry',
            'Medicinal plants',
            'Ethnobotany',
            'Traditional medicine',
            'Natural product isolation',
            'Pharmacognosy',
            'Marine natural products',
            'Bioprospecting',
            'Natural product synthesis',
        ];

        $randomKeyword = $keywords[array_rand($keywords)];

        // Fetch data from Europe PMC API
        $response = Http::get('https://www.ebi.ac.uk/europepmc/webservices/rest/search', [
            'query' => $randomKeyword,
            'format' => 'json',
            'pageSize' => 10,
        ]);

        $randomIndex = rand(0, 10);

        // Extract citation details from the response
        $citationData = $response->json('resultList.result.'.$randomIndex);
        $doi = $citationData['doi'] ?? null;

        while (! $doi) {
            if ($randomIndex == 0) {
                $randomIndex = 10;
            } else {
                $randomIndex -= 1;
            }
            $citationData = $response->json('resultList.result.'.$randomIndex);
            $doi = $citationData['doi'] ?? null;
        }

        // Extracting individual fields
        $title = $citationData['title'] ?? null;
        $authors = $citationData['authorString'] ?? null;
        $journalTitle = $citationData['journalTitle'] ?? null;
        $pubYear = $citationData['pubYear'] ?? null;
        $volume = $citationData['volume'] ?? null;
        $issue = $citationData['issue'] ?? null;
        $pageInfo = $citationData['pageInfo'] ?? null;

        // Construct citation text
        $citationText = '';
        if ($journalTitle && $title && $pubYear && $volume && $issue && $pageInfo) {
            $citationText = "$journalTitle. $title. $pubYear; $volume($issue): $pageInfo";
        }

        return [
            'doi' => $doi.rand(0, 100),
            'title' => $title,
            'authors' => $authors,
            'citation_text' => $citationText,
        ];
    }
}
