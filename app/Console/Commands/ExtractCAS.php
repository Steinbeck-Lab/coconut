<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;

class ExtractCAS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-cas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Molecule::whereNull('cas')->whereNotNull('synonyms')->select('id', 'synonyms')->chunk(10000, function ($mols) {
            $data = [];
            $pattern = "/\b[1-9][0-9]{1,5}-\d{2}-\d\b/";
            foreach ($mols as $mol) {
                $casIds = preg_grep($pattern, $mol->synonyms);
                array_push($data, [
                    'id' => $mol->id,
                    'cas' => array_values($casIds),
                ]);
                $this->info("Mapped and updated: $mol->id");
            }
            $this->insertBatch($data);
        });

        Molecule::whereNotNull('cas')->select('id', 'cas')->chunk(10000, function ($mols) {
            $data = [];
            $pattern = "/\b[1-9][0-9]{1,5}-\d{2}-\d\b/";
            foreach ($mols as $mol) {
                $inputArray = $mol->cas;

                // Filter array to remove elements that start with "InChI"
                $filteredArray = array_filter($inputArray, function ($element) {
                    return ! str_starts_with($element, 'InChI');
                });

                // Modify elements that start with "CAS-"
                $modifiedArray = array_map(function ($element) {
                    if (str_starts_with($element, 'CAS-')) {
                        return substr($element, 4); // Remove "CAS-" prefix
                    }

                    return $element;
                }, $filteredArray);

                // Remove duplicates
                $uniqueArray = array_unique($modifiedArray);

                // Reset array keys
                $uniqueArray = array_values($uniqueArray);

                array_push($data, [
                    'id' => $mol->id,
                    'cas' => $uniqueArray,
                ]);

                $this->info("Mapped and updated: $mol->id");
            }
            $this->insertBatch($data);
        });
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
                        'cas' => $row['cas'],
                    ]
                );
            }
        });
    }
}
