<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;
use Log;

class ImportSTOUTIUPACNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-iupac-data {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command to import STOUT IUPAC names from a file.
     *
     * This method performs the following steps:
     * 1. Checks if the specified file exists and is readable.
     * 2. Reads the file as a comma-separated values (CSV) format.
     * 3. Processes the data in batches and inserts it into the database.
     * 4. Logs any errors encountered during processing.
     * 5. Outputs a success message upon completion.
     *
     * @return int Returns 0 on successful import, or 1 if the file is not found or not readable.
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

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
                if (! $header) {
                    $header = $row;
                    $header[0] = 'id';
                } else {
                    try {
                        $data[] = array_combine($header, $row);
                        $rowCount++;
                        if ($rowCount % $batchSize == 0) {
                            $this->insertBatch($data);
                            $data = [];
                        }
                    } catch (\ValueError $e) {
                        Log::info('An error occurred: '.$e->getMessage());
                        Log::info($rowCount++);
                    }
                }
            }
            fclose($handle);

            if (! empty($data)) {
                $this->insertBatch($data);
            }
        }

        $this->info('IUPAC data imported successfully!');

        return 0;
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
                        'iupac_name' => $row['iupac_name'],
                    ]
                );
            }
        });
    }
}
