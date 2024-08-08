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
    protected $description = 'Command description';

    public function handle()
    {
        $batchSize = 1000;
        $data = [];
        DB::table('molecules')
            ->where('active', true)
            ->orderBy('id')
            ->chunk($batchSize, function ($molecules) use ($data) {
                foreach ($molecules as $molecule) {
                    $score = $this->calculateAnnotationScore($molecule);
                    array_push($data, [
                        'id' => $molecule->id,
                        'annotation_level' => $score,
                    ]);
                }
                if (! empty($data)) {
                    $this->info('Updating row:'.$molecule->id);
                    $this->updateBatch($data);
                    $data = [];
                }
            });

        $this->info('Annotation scores generated successfully.');
    }

    /**
     * Insert a batch of data into the database.
     *
     * @return void
     */
    private function updateBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                Molecule::updateorCreate(
                    [
                        'id' => $row['id'],
                    ],
                    [
                        'annotation_level' => $row['annotation_level'],
                    ]
                );
            }
        });
    }

    protected function calculateAnnotationScore($molecule)
    {
        $casScore = $molecule->cas ? 1 : 0;
        $synonymsScore = $molecule->synonyms ? ($molecule->synonym_count >= 1 ? 1 : 0) : 0;
        $nameScore = $molecule->name ? 1 : 0;

        $literatureScore = $molecule->citation_count >= 2 ? 1 : ($molecule->citation_count >= 1 ? 0.5 : 0);
        $organismScore = $molecule->organism_count >= 1 ? 0 : ($molecule->organism_count >= 1 ? 0.5 : 0);
        $collectionsScore = $molecule->collection_count >= 2 ? 1 : ($molecule->collection_count >= 1 ? 0.5 : 0);

        $totalScore = ($literatureScore * 0.25) +
                        ($organismScore * 0.20) +
                        ($collectionsScore * 0.15) +
                        ($casScore * 0.15) +
                        ($synonymsScore * 0.15) +
                        ($nameScore * 0.10);

        $finalScore = round($totalScore * 5, 2);

        return ceil($finalScore);
    }
}
