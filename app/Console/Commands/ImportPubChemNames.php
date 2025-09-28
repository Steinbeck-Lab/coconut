<?php

namespace App\Console\Commands;

use App\Jobs\ImportPubChem;
use App\Models\Collection;
use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Log;

class ImportPubChemNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-pubchem-data-old {collection_id} {file?}';

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
        $file = $this->argument('file');

        if (! $file || ! file_exists($file) || ! is_readable($file)) {
            $collection_id = $this->argument('collection_id');

            if (! is_null($collection_id)) {
                $collections = Collection::where('id', $collection_id)->get();
            } else {
                $collections = Collection::where('status', 'DRAFT')->get();
            }

            foreach ($collections as $collection) {
                $batchJobs = [];
                $i = 0;
                $collection->molecules()->chunk(1000, function ($mols) use (&$batchJobs, &$i) {
                    foreach ($mols as $mol) {
                        array_push($batchJobs, new ImportPubChem($mol));
                    }
                    $i = $i + 1;
                });
                $batch = Bus::batch($batchJobs)->then(function (Batch $batch) {})->catch(function (Batch $batch, Throwable $e) {})->finally(function (Batch $batch) {})->name('Import PubChem '.$collection->id)
                    ->allowFailures()
                    ->onConnection('redis')
                    ->onQueue('default')
                    ->dispatch();
            }
        } else {
            $file = storage_path($file);

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

            $this->info('PubChem data imported successfully!');

            return 0;
        }
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
                        'canonical_smiles' => $row['canonical_smiles'],
                    ],
                    [
                        'name' => $row['name'],
                        'iupac_name' => $row['IUPAC_name'],
                        'synonyms' => explode('|', $row['synonyms']),
                    ]
                );
            }
        });
    }
}
