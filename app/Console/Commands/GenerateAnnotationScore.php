<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;

class GenerateAnnotationScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate annotation scores for all active molecules in the database.';

    /**
     * Execute the console command.
     *
     * This command processes all active molecules in the database, calculates an annotation score
     * for each molecule, and updates the database with the calculated scores. The processing is done
     * in chunks to efficiently handle large datasets without exhausting memory.
     *
     * @return void
     */
    public function handle()
    {
        $batchSize = 10000; // Number of molecules to process in each batch
        $data = []; // Array to store data for batch updating

        // Process active molecules in chunks to avoid memory exhaustion
        DB::table('molecules')
            ->where('active', true)
            ->orderBy('id')
            ->chunk($batchSize, function ($molecules) use (&$data) {
                foreach ($molecules as $molecule) {
                    // Calculate the annotation score for the current molecule
                    $score = $this->calculateAnnotationScore($molecule);

                    // Prepare data for batch update
                    array_push($data, [
                        'id' => $molecule->id,
                        'annotation_level' => $score,
                    ]);
                }

                // Update the database with the calculated scores in batch
                if (! empty($data)) {
                    $this->info('Updating rows up to molecule ID: '.end($data)['id']);
                    $this->updateBatch($data);
                    $data = []; // Reset the data array for the next batch
                }
            });

        // Ensure any remaining data is updated after the last chunk
        if (! empty($data)) {
            $this->updateBatch($data);
        }

        $this->info('Annotation scores generated successfully.');
    }

    /**
     * Update a batch of molecules with their annotation scores.
     *
     * This method wraps the update operation in a transaction to ensure atomicity and data consistency.
     *
     * @param  array  $data  Array of molecule data to update
     * @return void
     */
    private function updateBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                // Update the molecule's annotation level or create a new entry if it doesn't exist
                Molecule::updateOrCreate(
                    ['id' => $row['id']],
                    ['annotation_level' => $row['annotation_level']]
                );
            }
        });
    }

    /**
     * Calculate the annotation score for a given molecule.
     *
     * This method computes the score based on several factors such as CAS number, synonyms, name,
     * literature citations, organisms, and collections. Each factor contributes to the final score
     * with a specific weight.
     *
     * @param  \stdClass  $molecule  The molecule object containing its attributes
     * @return int The calculated annotation score (rounded and scaled)
     */
    protected function calculateAnnotationScore($molecule)
    {
        // Assign scores based on the presence and counts of various attributes
        $casScore = $molecule->cas ? 1 : 0;
        $synonymsScore = $molecule->synonyms ? ($molecule->synonym_count >= 1 ? 1 : 0) : 0;
        $nameScore = $molecule->name ? 1 : 0;
        $literatureScore = $molecule->citation_count >= 2 ? 1 : ($molecule->citation_count >= 1 ? 1 : 0);
        $organismScore = $molecule->organism_count >= 1 ? 1 : 0;
        $collectionsScore = $molecule->collection_count >= 2 ? 1 : ($molecule->collection_count >= 1 ? 1 : 0);

        // Calculate the weighted total score
        $totalScore = ($literatureScore * 0.25) +
                      ($organismScore * 0.20) +
                      ($collectionsScore * 0.15) +
                      ($casScore * 0.15) +
                      ($synonymsScore * 0.15) +
                      ($nameScore * 0.10);

        // Scale and round the score to fit within the desired range
        $finalScore = round($totalScore * 5, 2);

        // Return the final score, rounded up to the nearest integer
        return ceil($finalScore);
    }
}
