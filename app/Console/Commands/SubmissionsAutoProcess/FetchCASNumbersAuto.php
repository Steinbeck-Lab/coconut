<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Models\Collection;
use App\Models\Molecule;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class FetchCASNumbersAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:fetch-cas-numbers {collection_id? : The ID of the collection to fetch CAS numbers for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch CAS numbers from Common Chemistry API for molecules that need them';

    /**
     * Configuration variables
     */
    private $batchSize = 100; // Number of molecules per batch

    private $apiBaseUrl = 'https://commonchemistry.cas.org/api';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');

        if ($collection_id) {
            $collection = Collection::find($collection_id);
            if (! $collection) {
                Log::error("Collection with ID {$collection_id} not found.");
                $this->error("Collection with ID {$collection_id} not found.");

                return 1;
            }
            Log::info("Fetching CAS numbers for collection ID: {$collection_id}");
        } else {
            Log::info('Fetching CAS numbers for all molecules');
        }

        // Build query for molecules that need CAS numbers
        $query = Molecule::query()
            ->where('active', true);
        if ($collection_id) {
            $query->whereHas('entries', function ($q) use ($collection_id) {
                $q->where('collection_id', $collection_id);
            });
        }

        // Exclude molecules that have failed CAS fetch previously (unless retrying)
        $query->where(function ($q) {
            $q->whereNull('curation_status->fetch-cas->status')
                ->orWhereNotIn('curation_status->fetch-cas->status', ['completed']);
        });

        $totalCount = $query->count();
        if ($totalCount === 0) {
            Log::info('No molecules found that need CAS number fetching.');

            return 0;
        }

        Log::info("Found {$totalCount} molecules that need CAS number fetching");

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat(' %current%/%max% molecules [%bar%] %percent%% %elapsed%/%estimated% %memory%');
        $progressBar->start();

        $successCount = 0;
        $failCount = 0;
        $notFoundCount = 0;

        // Process molecules in chunks
        $query->chunkById($this->batchSize, function ($molecules) use ($progressBar, &$successCount, &$failCount, &$notFoundCount) {
            foreach ($molecules as $molecule) {
                try {
                    Log::info("Fetching CAS number for molecule {$molecule->id}");

                    $casNumber = $this->fetchCASFromAPI($molecule);

                    if ($casNumber) {
                        // Update molecule with CAS number (store as array)
                        $currentCas = $molecule->cas ?? [];
                        if (! is_array($currentCas)) {
                            $currentCas = [];
                        }

                        // Add the new CAS number if not already present
                        if (! in_array($casNumber, $currentCas)) {
                            $currentCas[] = $casNumber;
                        }

                        // Persist CAS value
                        $molecule->update([
                            'cas' => $currentCas,
                        ]);

                        // Use central helper to update curation status
                        // Uses global helper from app/helpers.php
                        updateCurationStatus($molecule->id, 'fetch-cas', 'completed', null);

                        Log::info("Successfully fetched CAS number {$casNumber} for molecule {$molecule->id}");
                        $successCount++;
                    } else {
                        // Mark as not found using central helper (mark completed so we don't keep retrying)
                        // Uses global helper from app/helpers.php
                        updateCurationStatus($molecule->id, 'fetch-cas', 'completed', 'not_found');

                        Log::warning("No CAS number found for molecule {$molecule->id}");
                        $notFoundCount++;
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to fetch CAS number for molecule {$molecule->id}: ".$e->getMessage());

                    // Mark as failed
                    // Use central helper to mark failure
                    try {
                        updateCurationStatus($molecule->id, 'fetch-cas', 'failed', $e->getMessage());
                    } catch (\Throwable $updateError) {
                        Log::error("Failed to update curation status for molecule {$molecule->id}: ".$updateError->getMessage());
                    }

                    $failCount++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();

        Log::info("CAS number fetch process completed. Success: {$successCount}, Not Found: {$notFoundCount}, Failed: {$failCount}");

        return 0;
    }

    /**
     * Fetch CAS number from Common Chemistry API
     */
    private function fetchCASFromAPI(Molecule $molecule): ?string
    {
        // Try candidates in order: InChIKey, SMILES.
        // Common CAS Registry is doing an exact match on SMILES. Since SMILES of our molecules are standardized using CMS pre-processing pipeline, our canonical SMILES may not match theirs.
        $candidates = [
            'InChIKey='.$molecule->standard_inchi_key,
            $molecule->canonical_smiles,
        ];

        foreach ($candidates as $candidate) {
            Log::info("Searching CAS number using candidate: {$candidate}");
            if (! $candidate) {
                continue;
            }

            $casNumber = $this->fetchCASFromCommonChemistryAPI($candidate, 'search');
            Log::info("Received CAS number: {$casNumber}");
            if ($casNumber) {
                //  Get the details using this CAS number.
                $details = $this->fetchCASFromCommonChemistryAPI($casNumber, 'detail');
                if (! $details) {
                    Log::warning("Failed to fetch details for CAS number: {$casNumber}");

                    continue;
                }
                // Use the SMILE (if not then use Canonical SMILE), InChI, InChIKey from details to verify if this is the correct molecule.
                // First need to standardise the SMILES using CMS pre-processing pipeline.
                $isTheCorrectMolecule = $this->verifyMoleculeIdentity($molecule, $details);
                Log::info(sprintf('Is the correct molecule: %s', $isTheCorrectMolecule ? 'yes' : 'no'));

                if ($isTheCorrectMolecule) {
                    return $casNumber;
                }
            }
        }

        return null;
    }

    /**
     * Single search helper that queries the Common Chemistry /search endpoint.
     */
    private function fetchCASFromCommonChemistryAPI(string $query, string $searchType = 'search'): string|array|null
    {
        if ($searchType === 'detail') {
            $url = "{$this->apiBaseUrl}/detail";
            $params = ['cas_rn' => $query];
        } else {
            $url = "{$this->apiBaseUrl}/search";
            $params = ['q' => $query];
        }

        $response = $this->makeAPIRequest($url, $params);

        if ($searchType === 'detail') {
            return $this->extractDetailsFromResponse($response);
        } else {
            $extractedCAS = $this->extractCASFromResponse($response);

            return $extractedCAS;
        }
    }

    /**
     * Make API request with rate limiting
     */
    private function makeAPIRequest(string $url, array $params): ?object
    {
        $response = RateLimiter::attempt(
            'common-chemistry-api',
            1, // 1 request per 2 seconds (30 requests per minute)
            function () use ($url, $params) {
                return $this->doActualRequest($url, $params);
            },
            2 // 2 seconds
        );

        if ($response === false) {
            $waitTime = RateLimiter::availableIn('common-chemistry-api');
            Log::warning("Common Chemistry API rate limited (wait: {$waitTime}s). Waiting and retrying request for: {$url}");
            sleep($waitTime);
            $response = $this->makeAPIRequest($url, $params);
        }

        if ($response === true || ! is_object($response) || ! method_exists($response, 'json')) {
            Log::warning('Rate limiter returned invalid response type: '.gettype($response));

            return null;
        }

        return $response;
    }

    /**
     * Make actual HTTP request
     */
    private function doActualRequest(string $url, array $params): ?\Illuminate\Http\Client\Response
    {
        $maxRetries = 3;
        $baseDelay = 1.0;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(120)
                    ->connectTimeout(60)
                    ->withHeaders([
                        'X-API-KEY' => config('services.cas.cas_key'),
                    ])
                    ->get($url, $params);

                if ($response->successful()) {
                    return $response;
                }

                Log::warning("HTTP request failed for URL: {$url}, Status: ".$response->status()." (attempt $attempt/$maxRetries)");
            } catch (\Exception $e) {
                Log::warning("HTTP request exception (attempt $attempt/$maxRetries) for URL: {$url}, Error: ".$e->getMessage());

                if ($attempt < $maxRetries && (
                    strpos($e->getMessage(), 'SSL routines::unexpected eof') !== false ||
                    strpos($e->getMessage(), 'Connection timeout') !== false ||
                    strpos($e->getMessage(), 'Connection reset') !== false ||
                    strpos($e->getMessage(), 'timeout') !== false
                )) {
                    $delay = $baseDelay * $attempt + (rand(0, 500) / 1000);
                    Log::info("Retrying after {$delay}s due to connection issue...");
                    usleep($delay * 1000000);

                    continue;
                }
            }

            if ($attempt < $maxRetries) {
                $delay = $baseDelay * $attempt;
                usleep($delay * 1000000);
            }
        }

        return null;
    }

    /**
     * Extract CAS number from API response
     *
     * Response structure: {"count": N, "results": [{"rn": "CAS-NUMBER", "name": "...", ...}]}
     */
    private function extractCASFromResponse($response): ?string
    {
        if (! $response || ! method_exists($response, 'json')) {
            return null;
        }

        $data = $response->json();

        if (! $data || ! isset($data['results'])) {
            return null;
        }

        // Check if we have any results
        if ($data['count'] > 0 && is_array($data['results']) && count($data['results']) > 0) {
            // Return the first result's CAS Registry Number (rn)
            if (isset($data['results'][0]['rn'])) {
                return $data['results'][0]['rn'];
            }
        }

        return null;
    }

    /**
     * Extract details (smile, canonicalSmile, inchi, inchiKey) from detail API response
     *
     * Response structure: {"smile": "...", "canonicalSmile": "...", "inchi": "...", "inchiKey": "...", ...}
     */
    private function extractDetailsFromResponse($response): ?array
    {
        if (! $response || ! method_exists($response, 'json')) {
            return null;
        }

        $data = $response->json();

        if (! $data) {
            return null;
        }

        // Extract smile, inchi, and inchiKey from the detail response
        return [
            'smile' => $data['smile'] ?? null,
            'canonical_smiles' => $data['canonicalSmile'] ?? null,
            'inchi' => $data['inchi'] ?? null,
            'inchikey' => $data['inchikey'] ?? null,
        ];
    }

    /**
     * Verify if the fetched details correspond to the original molecule
     */
    private function verifyMoleculeIdentity(Molecule $originalMolecule, array $fetchedDetails): bool
    {
        $API_URL = config('services.cheminf.internal_api_url');
        $smiles = $fetchedDetails['smile'] ?: $fetchedDetails['canonical_smiles'];
        if (! $smiles) {
            Log::warning('No SMILES data available for molecule verification', ['molecule_id' => $originalMolecule->id]);

            return false;
        }
        $ENDPOINT = $API_URL.'chem/coconut/pre-processing?smiles='.urlencode($smiles).'&_3d_mol=false&descriptors=false';

        $standardized_smiles = null;
        try {
            $response = Http::timeout(600)->get($ENDPOINT);
            if ($response->successful()) {
                $data = $response->json();
                if (array_key_exists('standardized', $data)) {
                    $standardized_smiles = $data['standardized']['representations']['canonical_smiles'];
                }
            }
        } catch (RequestException $e) {
            Log::error('Request Exception occurred: '.$e->getMessage().' - '.$fetchedDetails['smile'], ['code' => $e->getCode()]);
        } catch (\Exception $e) {
            Log::error('An unexpected exception occurred: '.$e->getMessage().' - '.$fetchedDetails['smile']);
        }

        if ($standardized_smiles === null) {
            return false;
        }

        return $standardized_smiles === $originalMolecule->canonical_smiles;
    }
}
