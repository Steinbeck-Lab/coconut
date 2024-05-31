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
    protected $signature = 'app:generate-score {offset?} {end?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        $batchSize = 1000;

        $offset = 0;
        $end = 100000000;

        $_offset = $this->argument('offset');
        $_end = $this->argument('end');

        if ($_offset) {
            $offset = $_offset;
        }

        if ($_end) {
            $end = $_end;
        }

        while (true) {
            $molecules = DB::table('molecules')
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($molecules->isEmpty() || $offset >= $end) {
                break;
            }

            $data = [];

            foreach ($molecules as $molecule) {
                $score = $this->calculateAnnotationScore($molecule);
                array_push($data, [
                    'id' => $molecule->id,
                    'annotation_level' => $score,
                ]);
            }

            $this->insertBatch($data);

            $offset += $batchSize;
            $this->info("Processed batch starting from offset $offset");
        }

        $this->info('Annotation scores generated successfully.');
    }

    /**
     * Insert a batch of data into the database.
     *
     * @return void
     */
    private function insertBatch(array $data)
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
        $literatureCount = DB::table('citables')
            ->where('citable_id', $molecule->id)
            ->where('citable_type', 'App\Models\Molecule')
            ->count();

        $organismCount = DB::table('molecule_organism')
            ->where('molecule_id', $molecule->id)
            ->count();

        $collectionsCount = DB::table('collection_molecule')
            ->where('molecule_id', $molecule->id)
            ->count();

        $casScore = $molecule->cas ? 1 : 0;
        $synonymsScore = $molecule->synonyms ? (count(json_decode($molecule->synonyms)) >= 1 ? 1 : 0) : 0;
        $nameScore = $molecule->name ? 1 : 0;

        $literatureScore = $literatureCount >= 2 ? 1 : ($literatureCount >= 1 ? 0.5 : 0);
        $organismScore = $organismCount >= 2 ? 1 : ($organismCount >= 1 ? 0.5 : 0);
        $collectionsScore = $collectionsCount >= 2 ? 1 : ($collectionsCount >= 1 ? 0.5 : 0);

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
