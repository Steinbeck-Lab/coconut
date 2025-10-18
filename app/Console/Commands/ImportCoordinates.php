<?php

namespace App\Console\Commands;

use App\Models\Structure;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-coordinates {file}';

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
            Log::error('File not found or not readable.');

            return 1;
        }

        $json = file_get_contents($file);
        $json_data = json_decode($json, true);
        if ($json === false) {
            exit('Error reading the JSON file');
        }

        $batchSize = 10000; // Number of molecules to process in each batch
        $totalElements = count($json_data);

        for ($i = 0; $i < $totalElements; $i += $batchSize) {
            Log::info('Processing batch '.($i / $batchSize + 1).' of '.ceil($totalElements / $batchSize));
            $batch = array_slice($json_data, $i, $totalElements - $i < $batchSize ? $totalElements - $i : $batchSize, true);
            $this->insertBatch($batch);
        }

        Log::info('Coordinates data imported successfully.');
    }

    /**
     * Update a batch of data into the database.
     *
     * @return void
     */
    private function insertBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $id => $molecule) {
                $structure = Structure::firstOrNew(['molecule_id' => $id]);
                if (is_null($structure['2d']) && isset($molecule['2d'])) {
                    $structure['2d'] = json_encode($molecule['2d']);
                }
                if (is_null($structure['3d']) && isset($molecule['3d'])) {
                    $structure['3d'] = json_encode($molecule['3d']);
                }
                $structure->save();
            }
        });
    }
}
