<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;
use Log;

class ImportLexichemIUPACNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-lexichem-iupac-names {file}';

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
        $this->info('File found and readable. '.now());

        $batchSize = 10000;
        $header = null;
        $data = [];
        $rowCount = 0;

        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, "\t", '"')) !== false) {
                if (! $header) {
                    $header[0] = 'smiles';
                    $header[1] = 'identifier';
                    $header[2] = 'iupac_name';
                } else {
                    try {
                        $data[] = array_combine($header, $row);

                        $rowCount++;
                        if ($rowCount % $batchSize == 0) {
                            $this->insertBatch($data);
                            $this->info('Rows inserted: '.$rowCount);
                            $data = [];
                        }
                    } catch (\ValueError $e) {
                        Log::info('An error occurred: '.$e->getMessage());
                        Log::info((string) $rowCount++);
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
                // Molecule::disableAuditing();
                $molecule = Molecule::where('identifier', $row['identifier'])->first();
                // $current_iupac_name = $molecule->iupac_name;
                $molecule['iupac_name'] = $row['iupac_name'];
                $molecule['name'] ??= $row['iupac_name'];
                $molecule->save();
                // DB::statement(
                //     "UPDATE molecules
                // SET (iupac_name) = ({$row['iupac_name']})
                // WHERE identifier = {$row->identifier};"
                // );
                // Molecule::enableAuditing();
                // customAuditLog('import-lexichem-iupac-names', [$molecule], 'iupac_name', $current_iupac_name, $row['iupac_name']);

            }
        });
    }
}
