<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Log;

class ImportCHEMBLNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-chembl-names {file}';

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

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, '	', '"')) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    try {
                        $data[] = array_combine($header, $row);
                    } catch (\ValueError $e) {
                        Log::info('An error occurred: '.$e->getMessage());
                    }
                }
            }
            fclose($handle);
        }
        $this->info('CHEMBL data imported to temp!');

        foreach ($data as $row) {
            $updates[$row['CHEMBL_ACCESSION']] = $row['NAME'];
        }

        DB::table('molecules')
            ->select('id', 'name', 'iupac_name', 'synonyms')
            ->where('name', 'ilike', 'SCHEMBL%')
            ->orderBy('id')
            ->chunkById($batchSize, function ($rows) use ($updates) {
                DB::transaction(function () use ($rows, $updates) {
                    foreach ($rows as $row) {
                        $key = strtoupper($row->name);
                        if (array_key_exists($key, $updates)) {
                            $synonyms = json_decode($row->synonyms) ?? [];
                            array_unshift($synonyms, $row->name);
                            $newName = $updates[$key];
                            DB::table('molecules')
                                ->where('id', $row->id)
                                ->update([
                                    'name' => $newName,
                                    'synonyms' => $synonyms
                                ]);
                        }else{
                            $newName = $row->iupac_name;
                            if($newName){
                                $synonyms = json_decode($row->synonyms) ?? [];
                                array_unshift($synonyms, $row->name);
                                DB::table('molecules')
                                    ->where('id', $row->id)
                                    ->update([
                                        'name' => $newName,
                                        'synonyms' => $synonyms
                                    ]);
                            }
                        }
                    }
                });
            });

        $this->info('CHEMBL data imported successfully!');

    }
}
