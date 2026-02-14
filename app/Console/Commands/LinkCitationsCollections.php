<?php

namespace App\Console\Commands;

use App\Models\Citation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LinkCitationsCollections extends Command
{
    protected $signature = 'coconut:link-citations-collections';

    protected $description = 'Links citations to their respective collections based on entries data';

    public function handle()
    {
        $doiRegex = '/\b(10[.][0-9]{4,}(?:[.][0-9]+)*)\b/';
        $lastId = 0;
        $batchSize = 1000;

        // Get total count of entries with citations
        $totalCount = DB::selectOne('SELECT COUNT(*) as count FROM entries WHERE doi IS NOT NULL AND collection_id IS NOT NULL');
        $total = $totalCount->count;

        $this->info("Processing {$total} entries with citations...");

        // Create progress bar
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        // fetch entries with citations using raw query
        while (true) {
            $entries = DB::select('SELECT id, collection_id, doi FROM entries WHERE id > ? AND doi IS NOT NULL AND collection_id IS NOT NULL ORDER BY id LIMIT ?', [$lastId, $batchSize]);

            if (empty($entries)) {
                break;
            }

            $batchLinks = [];

            foreach ($entries as $entry) {
                $lastId = $entry->id;

                // Split DOIs by | or ## into an array
                $dois = preg_split('/\||\#\#/', $entry->doi);

                // Trim whitespace and filter out empty values
                $dois = array_filter(array_map('trim', $dois));

                // process each DOI
                foreach ($dois as $doi) {
                    // process individual DOI
                    if ($doi && $doi != '') {
                        if (preg_match($doiRegex, $doi)) {
                            $citation = $this->fetchDOICitation($doi);
                        } else {
                            $citation = $this->fetchCitation($doi);
                        }

                        // If citation found, link it with the collection_id
                        if ($citation && isset($citation->id)) {
                            $batchLinks[] = [
                                'citation_id' => $citation->id,
                                'citable_type' => 'App\\Models\\Collection',
                                'citable_id' => $entry->collection_id,
                            ];
                        }
                    }
                }

                // Advance progress bar for each entry processed
                $progressBar->advance();
            }

            // Insert batch links into citables table using raw insert
            if (! empty($batchLinks)) {
                $this->insertCitableLinks($batchLinks);
            }
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('All citations linked to their respective collections.');
    }

    private function insertCitableLinks(array $links)
    {
        // Build the insert query with ON CONFLICT DO NOTHING for PostgreSQL or IGNORE for MySQL
        $values = [];
        $bindings = [];

        foreach ($links as $link) {
            $values[] = '(?, ?, ?)';
            $bindings[] = $link['citation_id'];
            $bindings[] = $link['citable_type'];
            $bindings[] = $link['citable_id'];
        }

        $valuesString = implode(', ', $values);

        $query = "INSERT INTO citables (citation_id, citable_type, citable_id) VALUES {$valuesString} ON CONFLICT DO NOTHING"; // PostgreSQL

        DB::insert($query, $bindings);
    }

    public function fetchDOICitation($doi)
    {
        $dois = $this->extract_dois($doi);

        foreach ($dois as $doi) {
            if ($doi) {
                // Create or find citation with DOI using database-agnostic approach
                $citation = DB::selectOne('SELECT * FROM citations WHERE doi = ?', [$doi]);

                return $citation;
            }
        }
    }

    public function fetchCitation($citation_text)
    {
        // First try to find existing citation
        $citation = DB::selectOne('SELECT * FROM citations WHERE citation_text = ?', [$citation_text]);

        return $citation;
    }

    public function extract_dois($input_string): array
    {
        $dois = [];
        $matches = [];
        // Regex pattern to match DOIs
        $pattern = '/(10\.\d{4,}(?:\.\d+)*\/\S+(?:(?!["&\'<>])\S))/i';
        // Extract DOIs using preg_match_all
        preg_match_all($pattern, $input_string, $matches);
        // Add matched DOIs to the dois array
        foreach ($matches[0] as $doi) {
            $dois[] = $doi;
        }

        // Check if the dois are split properly (especially considering that non dois are there).
        return $dois;
    }
}
