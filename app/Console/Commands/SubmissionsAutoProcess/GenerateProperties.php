<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-properties-auto {collection_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate properties from a CSV of SMILES using RDKit and CDK (Python script)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collectionId = $this->argument('collection_id');

        $outputTSV = 'properties'.($collectionId ? '_'.$collectionId : '').'.tsv';
        $scriptPath = app_path('Scripts/generate_properties.py');

        // Fetch data from DB and write to temp CSV
        Log::info('Fetching molecules from database...');

        $query = '';
        $rows = [];
        if ($collectionId) {
            $query = 'SELECT id, canonical_smiles, identifier FROM molecules m WHERE NOT EXISTS (SELECT 1 FROM properties p WHERE p.molecule_id = m.id) AND m.id in (SELECT DISTINCT molecule_id from collection_molecule where collection_id=?)';
            $rows = DB::select($query, [$collectionId]);
        } else {
            $query = 'SELECT id, canonical_smiles, identifier FROM molecules m WHERE NOT EXISTS (SELECT 1 FROM properties p WHERE p.molecule_id = m.id)';
            $rows = DB::select($query);
        }

        if (empty($rows)) {
            Log::warning('No molecules found to process.');

            return 0;
        }

        $tmpCsv = storage_path('app/tmp/properties_input'.($collectionId ? '_'.$collectionId : '').'.csv');
        $handle = fopen($tmpCsv, 'w');
        // Write header
        fputcsv($handle, ['id', 'canonical_smiles', 'identifier']);
        foreach ($rows as $row) {
            fputcsv($handle, [(string) $row->id, $row->canonical_smiles, $row->identifier]);
        }
        fclose($handle);
        Log::info('Temporary CSV created: '.$tmpCsv);

        $result = $this->runPythonProcessing($scriptPath, $tmpCsv, $outputTSV);

        $tsv_file = storage_path('app/tmp/'.$outputTSV);
        if ($result === 0 && file_exists($tsv_file)) {
            Log::info('Calling ImportProperties with generated output...');
            $exitCode = $this->call('coconut:import-properties', [
                'file' => 'app/tmp/'.$outputTSV,
            ]);
            if ($exitCode === 0) {
                Log::info('✅ ImportProperties completed successfully.');
            } else {
                Log::error('❌ ImportProperties failed.');
            }
        } else {
            Log::error('❌ Properties generation failed.');
        }

        $this->cleanupFiles([$tmpCsv, $tsv_file]);

        return $result;
    }

    /**
     * Clean up temporary files (CSV, JSON) even on failure.
     */
    private function cleanupFiles(array $files)
    {
        foreach ($files as $file) {
            if ($file && file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Run the Python script to process properties.
     */
    private function runPythonProcessing($scriptPath, $tmpCsv, $outputTSV)
    {
        Log::info('Running Python script to generate properties...');

        $pythonBinary = '/app/cc/bin/python'; // Use Python from container venv
        $process = new Process([
            $pythonBinary,
            $scriptPath,
            $tmpCsv,
            '--output-tsv',
            $outputTSV,
        ]);
        $process->setTimeout(null); // No timeout

        try {
            $process->mustRun(function ($type, $buffer) {
                echo $buffer;
            });
            Log::info('✨ Properties generation complete!');
        } catch (ProcessFailedException $exception) {
            Log::error('❌ Python script failed: '.$exception->getMessage());

            return 1;
        }

        return 0;
    }
}
