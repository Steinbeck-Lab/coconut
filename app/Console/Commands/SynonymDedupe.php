<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SynonymDedupe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:synonym-dedupe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This deduplicates synonyms (not case sensitive)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('molecules')
            ->chunkById(1000, function (Collection $molecules) {
                $records = []; // Ensure records are reset for each chunk

                foreach ($molecules as $molecule) {
                    $uniqueSynonyms = [];
                    $synonyms = json_decode($molecule->synonyms, true);
                    if ($synonyms) {
                        $uniqueSynonyms = $this->removeSimilarStrings($synonyms);
                    }

                    if ($synonyms != $uniqueSynonyms) {
                        $records[] = [
                            'id' => $molecule->id,
                            'synonyms' => $uniqueSynonyms,
                        ];
                    }
                }

                if (! empty($records)) {
                    $cases = [];
                    $ids = [];
                    $bindings = [];

                    foreach ($records as $record) {
                        $cases[] = 'WHEN ? THEN CAST(? AS JSON)';
                        $bindings[] = $record['id'];
                        $bindings[] = json_encode($record['synonyms']);
                        $ids[] = $record['id'];
                    }

                    $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));

                    $sql = 'UPDATE molecules SET synonyms = CASE id '.implode(' ', $cases)." END WHERE id IN ($ids_placeholder)";
                    $bindings = array_merge($bindings, $ids);

                    DB::update($sql, $bindings);
                }
            });
    }

    public function removeSimilarStrings($synonyms)
    {
        $uniqueSynonyms = [];
        $threshold = 90; // Similarity threshold (0-100)

        foreach ($synonyms as $synonym) {
            $isUnique = true;
            foreach ($uniqueSynonyms as $uniqueSynonym) {
                similar_text($synonym, $uniqueSynonym, $percent);
                if ($percent >= $threshold) {
                    $isUnique = false;
                    break;
                }
            }
            if ($isUnique) {
                $uniqueSynonyms[] = $synonym;
            }
        }

        return $uniqueSynonyms;
    }
}
