<?php

namespace App\Console\Commands;

use App\Models\Properties;
use DB;
use Illuminate\Console\Command;
use Log;

class ImportClassyFire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-classyfire  {file}';

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

        Log::info('Reading file: '.$file);

        $batchSize = 10000;
        $header = null;
        $data = [];
        $rowCount = 0;

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, "\t")) !== false) {
                if (! $header) {
                    $header = $row;
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
                        Log::info((string) $rowCount++);
                    }
                    $this->info("Inserted: $rowCount");
                }
            }
            fclose($handle);

            if (! empty($data)) {
                $this->insertBatch($data);
            }
        }

        $this->info('ClassyFire data imported successfully!');

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
                Properties::updateorCreate(
                    [
                        'molecule_id' => $row['id'],
                    ],
                    [
                        'chemical_class' => str_replace('"', '', $row['chemical_class']),
                        'chemical_sub_class' => str_replace('"', '', $row['chemical_sub_class']),
                        'chemical_super_class' => str_replace('"', '', $row['chemical_super_class']),
                        'direct_parent_classification' => str_replace('"', '', $row['direct_parent_classification']),
                    ]
                );
            }
        });
    }
}
