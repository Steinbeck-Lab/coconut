<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\Organism;
use DB;
use Illuminate\Console\Command;

class UpdateNPAtlasSpeciesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:update-npatlas-species-data {file}';

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
                echo $entry->id.'-'.$entry->organism;
                echo "\r\n";
                $organisms = Organism::where('name', $entry->organism)->get();
                if ($organisms) {
                    foreach ($organisms as $organism) {
                        echo $organism->id.' - '.$entry->molecule_id;
                        echo "\r\n";
                        DB::table('molecule_organism')->where('organism_id', $organism->id)->where('molecule_id', $entry->molecule_id)->delete();
                    }
                }

                $organism = Organism::firstOrCreate(
                    ['name' => $row['ORGANISM']]
                );
                $entry->molecule->organisms()->syncWithoutDetaching([$organism->id => ['organism_parts' => '']]);

                $entry->organism = $row['ORGANISM'];
                $entry->save();
            }
        });
    }
}
