<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:generate-coordinates-auto {collection_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate 2D and 3D coordinates from a CSV of SMILES using RDKit (Python script)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collectionId = $this->argument('collection_id');

        $scriptPath = app_path('Scripts/generate_coordinates.py');
        $tmpCsv = storage_path('app/public/coordinates_input'.($collectionId ? '_'.$collectionId : '').'.csv');
        $outputJson = 'coordinates'.($collectionId ? '_'.$collectionId : '').'.json';

        // Fetch data from DB and write to temp CSV
        Log::info('Fetching molecules from database...');

        $query = '';
        $rows = [];
        if ($collectionId) {
            $query = 'SELECT id, canonical_smiles, identifier FROM molecules m WHERE NOT EXISTS (SELECT 1 FROM structures s WHERE s.molecule_id = m.id) AND m.id in (SELECT DISTINCT molecule_id from collection_molecule where collection_id=?)';
            $rows = DB::select($query, [$collectionId]);
        } else {
            $query = 'SELECT id, canonical_smiles, identifier FROM molecules m WHERE NOT EXISTS (SELECT 1 FROM structures s WHERE s.molecule_id = m.id)';
            $rows = DB::select($query);
        }

        if (empty($rows)) {
            Log::warning('No molecules found to process.');

            return 0;
        }

        $handle = fopen($tmpCsv, 'w');
        // Write header
        fputcsv($handle, ['id', 'canonical_smiles', 'identifier']);
        foreach ($rows as $row) {
            fputcsv($handle, [(string) $row->id, $row->canonical_smiles, $row->identifier]);
        }
        fclose($handle);
        Log::info('Temporary CSV created: '.$tmpCsv);

        $result = $this->runPythonProcessing($scriptPath, $tmpCsv, $outputJson);

        $json_file = storage_path('app/public/'.$outputJson);
        if ($result === 0 && file_exists($json_file)) {
            Log::info('Calling ImportCoordinates with generated output...');
            $exitCode = $this->call('coconut:import-coordinates', [
                'file' => 'app/public/'.$outputJson,
            ]);
            if ($exitCode === 0) {
                Log::info('✅ ImportCoordinates completed successfully.');
            } else {
                Log::error('❌ ImportCoordinates failed.');
            }
        } else {
            Log::error('❌ Coordinates generation failed.');
        }

        $this->cleanupFiles([$tmpCsv, $json_file]);

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
     * Run the Python script to process coordinates.
     */
    private function runPythonProcessing($scriptPath, $tmpCsv, $outputJson)
    {
        Log::info('Running Python script to generate coordinates...');

        $pythonBinary = '/app/cc/bin/python'; // Use Python from container venv
        $process = new Process([
            $pythonBinary,
            $scriptPath,
            $tmpCsv,
            '--output-json',
            $outputJson,
        ]);
        $process->setTimeout(null); // No timeout

        try {
            $process->mustRun(function ($type, $buffer) {
                echo $buffer;
            });
            Log::info('✨ Coordinates generation complete!');
        } catch (ProcessFailedException $exception) {
            Log::error('❌ Python script failed: '.$exception->getMessage());

            return 1;
        }

        return 0;
    }
}
