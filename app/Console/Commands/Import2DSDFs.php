<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use App\Models\Structure;
use DB;
use Illuminate\Console\Command;

class Import2DSDFs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-2d-sdfs {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports 2D SDFs from a JSON file into Structures table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = storage_path($this->argument('file'));
        if (! file_exists($file) || ! is_readable($file)) {
            $this->error('File not found or not readable.');

            return 1;
        }

        $batchSize = 1000;
        $header = null;
        $data = [];
        $rowCount = 0;
        $json = file_get_contents($file);
        $json_data = json_decode($json, true);
        if ($json === false) {
            exit('Error reading the JSON file');
        }

        $batchSize = 10000; // Number of molecules to process in each batch
        $data = []; // Array to store data for batch updating
        $totalElements = count($json_data);

        for ($i = 0; $i < $totalElements; $i += $batchSize) {
            $this->info('Processing batch '.($i / $batchSize + 1).' of '.ceil($totalElements / $batchSize));
            $batch = array_slice($json_data, $i, $totalElements - $i < $batchSize ? $totalElements - $i : $batchSize);
            $this->insertBatch($batch);
        }

        $this->info('Annotation scores generated successfully.');
    }

    /**
     * Update a batch of data into the database.
     *
     * @return void
     */
    private function insertBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $identifier => $sdf_2d) {
                $molecule = Molecule::select('id')->where('identifier', $identifier)->first();
                $structure = new Structure;
                $structure['molecule_id'] = $molecule->id;
                $structure['2d'] = json_encode($sdf_2d);
                $structure->save();
            }
        });
    }
}
