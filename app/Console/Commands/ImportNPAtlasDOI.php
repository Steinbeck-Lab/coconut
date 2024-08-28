<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Entry;
use App\Models\Citation;
use DB;

class ImportNPAtlasDOI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-npatlas-doi {file}';

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
            while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    try {
                        $data[] = array_combine($header, $row);
                        $rowCount++;
                        if ($rowCount % $batchSize == 0) {
                            $this->updateBatch($data);
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
                $this->updateBatch($data);
            }
        }

        $this->info('NPAtlas data imported successfully!');

        return 0;
    }

    /**
     * Update a batch of data into the database.
     *
     * @return void
     */
    private function updateBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $row) {
                $entry = Entry::where('collection_id', 31)->where('reference_id', $row['REFERENCE_ID'])->first();
                echo $entry->id.'-'.$entry->doi.'-'.$row['DOI'];
                echo "\r\n";
                $citation = Citation::firstOrCreate(
                    ['doi' => $row['DOI']]
                );
                if($entry->molecule){
                    $entry->molecule->citations()->syncWithoutDetaching($citation);
                    $entry->doi = $row['DOI'];
                    $entry->save();
                }
            }
        });
    }
}
