<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;

class Import3DSDFs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-3d-sdfs {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports 3D SDFs from a JSON file into Structures table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = storage_path($this->argument('file'));
        $file_suffix = (int) $file[strpos($file, '.json') - 1];
        $file_name_without_id = substr($file, 0, strpos($file, '.json') - 1);

        // loop runs though all the files
        for (; $file_suffix <= 18; $file_suffix++) {
            $file_name = $file_name_without_id.$file_suffix.'.json';
            $this->info('Starting loop for file '.$file_name);
            if (! file_exists($file_name) || ! is_readable($file_name)) {
                $this->error('File not found or not readable: '.$file_name);

                return 1;
            }

            $batchSize = 1000;
            $header = null;
            $data = [];
            $rowCount = 0;

            $json = file_get_contents($file);
            if ($json === false) {
                exit('Error reading the JSON file');
            }
            $this->info('File read');

            $json_data = json_decode($json, true);
            $this->info('Total elements successfully read: '.count($json_data));

            $batchSize = 10000; // Number of molecules to process in each batch
            $data = []; // Array to store data for batch updating

            $totalElements = count($json_data);
            $this->info('Total elements: '.$totalElements);
            for ($i = 0; $i < $totalElements; $i += $batchSize) {
                $this->info('Processing batch '.($i / $batchSize + 1).' of '.ceil($totalElements / $batchSize));
                $batch = array_slice($json_data, $i, $totalElements - $i < $batchSize ? $totalElements - $i : $batchSize);
                $this->insertBatch($batch);
            }

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
            foreach ($data as $identifier => $sdf_3d) {
                $structure = Molecule::where('identifier', $identifier)->first()->structures;
                $structure['3d'] = json_encode($sdf_3d);
                $structure->save();
            }
        });
    }
}
