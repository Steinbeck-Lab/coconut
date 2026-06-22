<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Molecule;
use App\Models\Properties;
use App\Support\NpClassifierResults;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Classify extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'coconut:npclassify-old {collection_id?} {--force : Re-classify molecules that already have NP Classifier data}';

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
            $query = $collection->molecules();
            $this->info("Processing molecules from collection ID: {$collection_id}.");
        } else {
            // Process all molecules
            $query = Molecule::query();
            $this->info('Processing all molecules in the database.');
        }

        // Count the total number of molecules for the progress bar.
        $totalCount = $query->count();
        if ($totalCount === 0) {
            $this->info('No molecules found associated with the collection.');

            return 0;
        }

        $this->info("Total molecules to process: {$totalCount}");
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $force = $this->option('force');

        // Process molecules in chunks.
        $query->select(['molecules.id', 'molecules.canonical_smiles'])
            ->when(! $force, function ($q) {
                $q->whereHas('properties', function ($propertiesQuery) {
                    $propertiesQuery->whereNull('np_classifier_pathway');
                });
            })
            ->when($force, function ($q) {
                $q->whereHas('properties', function ($propertiesQuery) {
                    $propertiesQuery->where(function ($classified) {
                        $classified->whereNotNull('np_classifier_pathway')
                            ->orWhereNotNull('np_classifier_superclass')
                            ->orWhereNotNull('np_classifier_class');
                    });
                });
            })
            ->chunk(100, function ($mols) use ($progressBar, $force) {
                $data = [];
                foreach ($mols as $mol) {
                    $id = $mol->id;
                    $canonical_smiles = $mol->canonical_smiles;

                    // Build endpoints.
                    $apiUrl = config('services.npclassifier.url').'?smiles=';
                    $endpoint = $apiUrl.urlencode($canonical_smiles);

                    // Log the start of processing for this molecule.
                    // $this->comment("Processing molecule ID: {$id}");

                    // Fetch coordinates from API.
                    $response_data = $this->fetchFromApi($endpoint, $canonical_smiles);
                    $response_data['id'] = $id;

                    // Accumulate data for batch insertion.
                    $data[] = $response_data;

                    // Advance the progress bar for each molecule.
                    $progressBar->advance();
                }

                // Insert the data in one transaction.
                $this->insertBatch($data, $force);
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
        $backoffMicroseconds = 10000; // 0.01 seconds = 10,000 microseconds

        while ($attempt < $maxRetries) {
            try {
                $response = Http::timeout(600)->get($endpoint);

                if ($response->successful()) {
                    return $response->json();
                }

                // Throttling: if we hit a 429, wait and retry.
                if ($response->status() === 429) {
                    Log::warning("Throttled (429) for SMILES: {$smiles}. Retrying in ".($backoffMicroseconds / 1000000).' second(s)...');
                    usleep($backoffMicroseconds);
                    $attempt++;

                    continue;
                }

                Log::error("Error fetching data for SMILES: {$smiles}, HTTP status: ".$response->status());

                return null;
            } catch (Throwable $e) {
                Log::error("Exception fetching data for SMILES: {$smiles}. ".$e->getMessage());
                usleep($backoffMicroseconds);
                $attempt++;
            }
        }

        return null;
    }

    /**
     * Insert a batch of data into the database.
     *
     * @return void
     */
    private function insertBatch(array $data, bool $force = false)
    {
        DB::transaction(function () use ($data, $force) {
            foreach ($data as $row) {
                $query = Properties::where('molecule_id', $row['id']);

                if (! $force) {
                    $query->whereNull('np_classifier_pathway');
                }

                $properties = $query->first();

                if ($properties) {
                    $properties->fill(NpClassifierResults::fromApiResponse($row));
                    $properties->save();
                }
            }
        });

    }
}
