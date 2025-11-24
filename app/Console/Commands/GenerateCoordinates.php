<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Structure;
use App\Services\CmsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCoordinates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:generate-coordinates-old {collection_id?}';

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
        $cmsClient = app(CmsClient::class);

        $moleculeIds->chunk(10000)->each(function ($idsChunk) use ($progressBar, $cmsClient) {
            $mols = Molecule::whereIn('id', $idsChunk)->select('id', 'canonical_smiles')->get();
            $data = [];
            foreach ($mols as $mol) {
                $id = $mol->id;
                $canonical_smiles = $mol->canonical_smiles;

                // Fetch coordinates from API.
                $d2 = $this->fetchFromApi($cmsClient, 'convert/mol2D', $canonical_smiles);
                $d3 = $this->fetchFromApi($cmsClient, 'convert/mol3D', $canonical_smiles);

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
     * @return mixed  (string|null) Returns the response body or null on failure.
     */
    private function fetchFromApi(CmsClient $cmsClient, string $endpoint, string $smiles)
    {
        $maxRetries = 3;
        $attempt = 0;
        $backoffSeconds = 0.01;

        while ($attempt < $maxRetries) {
            try {
                $response = $cmsClient->get($endpoint, [
                    'smiles' => $smiles,
                    'toolkit' => 'rdkit',
                ], false);

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
