<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Structure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:generate-coordinates {collection_id?}';

    /**
     * The console command description.
     */
    protected $description = 'Generates coordinates (2D/3D) for molecules either for a specific collection or for all molecules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');

        // Build the base query depending on whether a collection_id is provided.
        if (! is_null($collection_id)) {
            $collection = Collection::find($collection_id);

            if (! $collection) {
                $this->error("Collection with ID {$collection_id} not found.");

                return 1;
            }

            // Retrieve only molecules from this collection that do not have structures.
            $query = $collection->molecules()->doesntHave('structures');
        } else {
            // Retrieve all molecules missing structures.
            $query = Molecule::doesntHave('structures');
            $this->info('Processing all molecules missing structures.');
        }

        // Retrieve all molecule IDs that require processing (static list).
        $moleculeIds = $query->pluck('molecules.id');

        $totalCount = $moleculeIds->count();
        if ($totalCount === 0) {
            $this->info('No molecules found that require coordinate generation.');

            return 0;
        }

        $this->info("Total molecules to process: {$totalCount}");
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        // Process molecules in chunks using the static list of IDs.
        $moleculeIds->chunk(10000)->each(function ($idsChunk) use ($progressBar) {
            $mols = Molecule::whereIn('id', $idsChunk)->select('id', 'canonical_smiles')->get();
            $data = [];
            foreach ($mols as $mol) {
                $id = $mol->id;
                $canonical_smiles = $mol->canonical_smiles;

                // Build endpoints.
                $apiUrl = env('API_URL', 'https://api.cheminf.studio/latest/');
                $d2Endpoint = $apiUrl.'convert/mol2D?smiles='.urlencode($canonical_smiles).'&toolkit=rdkit';
                $d3Endpoint = $apiUrl.'convert/mol3D?smiles='.urlencode($canonical_smiles).'&toolkit=rdkit';

                // Fetch coordinates from API.
                $d2 = $this->fetchFromApi($d2Endpoint, $canonical_smiles);
                $d3 = $this->fetchFromApi($d3Endpoint, $canonical_smiles);

                // Accumulate data for batch insertion.
                $data[] = [
                    'id' => $id,
                    '2d' => $d2,
                    '3d' => $d3,
                ];

                // Advance the progress bar for each molecule.
                $progressBar->advance();
            }

            // Insert the data in one transaction.
            $this->insertBatch($data);
        });

        $progressBar->finish();
        $this->info("\nCoordinate generation process completed.");

        return 0;
    }

    /**
     * Make an HTTP GET request with basic retry/backoff handling (e.g. 429 Too Many Requests).
     *
     * @return mixed  (array|null) Returns the JSON-decoded response or null on failure.
     */
    private function fetchFromApi(string $endpoint, string $smiles)
    {
        $maxRetries = 3;
        $attempt = 0;
        $backoffSeconds = 0.01;

        while ($attempt < $maxRetries) {
            try {
                $response = Http::timeout(600)->get($endpoint);

                if ($response->successful()) {
                    return $response->body();
                }

                if ($response->status() === 429) {
                    Log::warning("Throttled (429) for SMILES: {$smiles}. Retrying in {$backoffSeconds} second(s)...");
                    sleep($backoffSeconds);
                    $attempt++;

                    continue;
                }

                Log::error("Error fetching data for SMILES: {$smiles}, HTTP status: ".$response->status());

                return null;
            } catch (Throwable $e) {
                Log::error("Exception fetching data for SMILES: {$smiles}. ".$e->getMessage());
                sleep($backoffSeconds);
                $attempt++;
            }
        }

        return null;
    }

    /**
     * Insert a batch of structure data into the database within a transaction.
     *
     * @return void
     */
    private function insertBatch(array $data)
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $mol) {
                // Skip insertion if both 2D and 3D coordinates are null.
                if (is_null($mol['2d']) && is_null($mol['3d'])) {
                    continue;
                }

                $structure = new Structure;
                $structure->molecule_id = $mol['id'];
                $structure['2d'] = json_encode($mol['2d']);
                $structure['3d'] = json_encode($mol['3d']);
                $structure->save();
            }
        });
    }
}
