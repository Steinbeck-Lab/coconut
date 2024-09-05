<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;

class ImportCoconutIDs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-ids {file}';

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
            while (($row = fgetcsv($handle, 0, "\t")) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                    $rowCount++;

                    if ($rowCount % $batchSize == 0) {
                        $this->insertBatch($data);
                        $data = [];
                    }
                }
            }
            fclose($handle);

            if (! empty($data)) {
                $this->insertBatch($data);
            }
        }

        $this->info('IDs imported successfully!');

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
                Molecule::where('id', $row['id'])
                    ->where('canonical_smiles', $row['canonical_smiles'])
                    ->update([
                        'identifier' => $row['identifier'],
                    ]);
            }
        });
    }
}
