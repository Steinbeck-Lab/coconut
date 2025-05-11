<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ImportPubChemAuto implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

    protected $failedIdsFile = 'pubchem_failed_molecules.json';

    /**
     * Create a new job instance.
     */
    public function __construct($molecule)
    {
        $this->molecule = $molecule;
    }

    /**
     * Get a unique identifier for the queued job.
     */
    public function uniqueId(): string
    {
        return $this->molecule->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ImportPubChem job started for molecule ID: '.$this->molecule->id);
        $this->fetchIUPACNameFromPubChem();
    }

    /**
     * Make a throttled HTTP GET request and sleep for 200ms afterward.
     */
    private function throttledGet(string $url)
    {
        $response = Http::get($url);
        // Sleep for 200 milliseconds to limit to 5 requests per second.
        usleep(200000);

        return $response;
    }

    /**
     * Store a failed molecule ID in the JSON file
     */
    /**
     * Store a failed molecule ID in the JSON file using Redis locks
     */
    /**
     * Store a failed molecule ID in the JSON file using direct Redis connection
     */
    private function storeFailedMolecule($reason = 'failed_cid_fetch')
    {
        try {
            $lockName = 'pubchem_failed_molecules_lock';

            // Get a cache lock
            $lock = Cache::lock($lockName, 30);
            $lockAcquired = false;

            // Try to acquire the lock with retries
            for ($attempt = 1; $attempt <= 5; $attempt++) {
                try {
                    $lockAcquired = $lock->get();

                    if ($lockAcquired) {
                        Log::info("Lock acquired for molecule ID: {$this->molecule->id} on attempt {$attempt}");
                        break;
                    }

                    Log::info("Lock attempt {$attempt} failed for molecule ID: {$this->molecule->id}");
                    usleep(200000 * $attempt);
                } catch (\Exception $e) {
                    Log::error("Error acquiring lock on attempt {$attempt}: ".$e->getMessage());
                }
            }

            if (! $lockAcquired) {
                Log::error("Failed to acquire lock for molecule ID: {$this->molecule->id}");

                return;
            }

            try {
                $failedIds = [];

                // Explicitly check file existence and log result
                $fileExists = Storage::disk('local')->exists($this->failedIdsFile);
                Log::info("File exists check for {$this->failedIdsFile}: ".($fileExists ? 'true' : 'false'));

                if ($fileExists) {
                    $fileContent = Storage::disk('local')->get($this->failedIdsFile);
                    Log::info('File content length: '.strlen($fileContent));
                    Log::info('File content first 100 chars: '.substr($fileContent, 0, 100));

                    $failedIds = json_decode($fileContent, true);

                    // Log JSON decode result
                    if ($failedIds === null) {
                        Log::error('JSON decode failed: '.json_last_error_msg());
                        $failedIds = [];
                    } else {
                        Log::info('Decoded JSON has '.count($failedIds).' entries');
                    }
                } else {
                    Log::info('Creating new failed molecules file');
                }

                // Check if molecule already exists in array
                if (isset($failedIds[$this->molecule->id])) {
                    Log::info("Molecule ID {$this->molecule->id} already exists in failed list, skipping");

                    return;
                }

                // Add the molecule to the array
                Log::info("Adding molecule ID {$this->molecule->id} to failed list");
                $failedIds[$this->molecule->id] = [
                    'molecule_id' => $this->molecule->id,
                    'reason' => $reason,
                    'failed_at' => now()->toDateTimeString(),
                    'smiles' => $this->molecule->canonical_smiles ?? null,
                ];

                // Convert to JSON and write
                $jsonContent = json_encode($failedIds, JSON_PRETTY_PRINT);
                Log::info('JSON content length for write: '.strlen($jsonContent));

                // Write the file - explicitly log the result
                $writeResult = Storage::disk('local')->put($this->failedIdsFile, $jsonContent);
                Log::info("Write result for molecule ID {$this->molecule->id}: ".($writeResult ? 'success' : 'failure'));

                if ($writeResult) {
                    // Verify the file was written correctly
                    $verifyContent = Storage::disk('local')->get($this->failedIdsFile);
                    $verifiedIds = json_decode($verifyContent, true);

                    if (isset($verifiedIds[$this->molecule->id])) {
                        Log::info("Verified molecule ID {$this->molecule->id} was successfully added to failed list");
                    } else {
                        Log::error("Verification failed - molecule ID {$this->molecule->id} not found in file after write");
                    }
                } else {
                    Log::error("Failed to write molecule ID {$this->molecule->id} to failed list");
                }
            } finally {
                // Release the lock
                if ($lockAcquired) {
                    $lock->release();
                    Log::info("Lock released for molecule ID: {$this->molecule->id}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Exception in storeFailedMolecule: '.$e->getMessage()."\n".$e->getTraceAsString());
        }
    }

    public function fetchIUPACNameFromPubChem()
    {
        $smilesURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/smiles/cids/TXT?smiles='
            .urlencode($this->molecule->canonical_smiles);
        $cidResponse = $this->throttledGet($smilesURL);

        if (! $cidResponse->successful()) {
            Log::error('Failed to fetch CID from PubChem for molecule ID: '.$this->molecule->id);
            // Store the failed molecule ID
            $this->storeFailedMolecule('failed_cid_fetch');

            return;
        }

        $cid = $cidResponse->body();

        if ($cid && trim($cid) != '0') {
            $cid = trim(preg_replace('/\s+/', ' ', $cid));
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.$cid.'/json';
            $dataResponse = $this->throttledGet($cidPropsURL);

            if (! $dataResponse->successful()) {
                Log::error('Failed to fetch data from PubChem for CID: '.$cid);
                $this->storeFailedMolecule('failed_data_fetch');

                return;
            }

            $data = $dataResponse->json();

            // Check if the key exists before proceeding.
            if (! isset($data['PC_Compounds'])) {
                Log::error('PC_Compounds key not found for CID: '.$cid);
                $this->storeFailedMolecule('missing_pc_compounds');

                return;
            }

            $props = $data['PC_Compounds'][0]['props'] ?? [];
            $IUPACName = null;
            foreach ($props as $prop) {
                if (
                    isset($prop['urn']['label'], $prop['urn']['name'], $prop['value']['sval']) &&
                    $prop['urn']['label'] === 'IUPAC Name' &&
                    $prop['urn']['name'] === 'Preferred'
                ) {
                    $IUPACName = $prop['value']['sval'];
                    break;
                } else {
                    Log::error('IUPAC Name not found in PubChem data for CID: '.$cid);
                }
            }

            // Fetch synonyms and CAS numbers.
            $this->fetchSynonymsCASFromPubChem($cid);

            if ($IUPACName) {
                $this->molecule->iupac_name = $IUPACName;
            }
            Log::info('PubChem IUPAC name: '.$IUPACName);
            $this->molecule->save();
        } else {
            Log::error('Invalid CID from PubChem for molecule ID: '.$this->molecule->id);
            $this->storeFailedMolecule('invalid_cid');
        }
    }

    public function fetchSynonymsCASFromPubChem($cid)
    {
        if ($cid && trim($cid) != '0') {
            $cid = trim(preg_replace('/\s+/', ' ', $cid));
            $synonymsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.$cid.'/synonyms/txt';

            $maxRetries = 3;
            $backoffSeconds = 2;
            $attempt = 0;
            $synResponse = null;

            do {
                $synResponse = $this->throttledGet($synonymsURL);
                $responseBody = $synResponse->body();
                if ($synResponse->successful() && strpos($responseBody, 'Status: 503') === false) {
                    break;
                }
                sleep($backoffSeconds);
                $attempt++;
            } while ($attempt < $maxRetries);

            if (! $synResponse || ! $synResponse->successful() || strpos($synResponse->body(), 'Status: 503') !== false) {
                $this->storeFailedMolecule('failed_synonyms_fetch');

                return;
            }

            $data = $synResponse->body();
            $synonyms = preg_split("/\r\n|\n|\r/", $data);
            Log::info('PubChem synonyms data: '.$data);
            Log::info('PubChem synonyms: '.$synonyms);
            if ($synonyms && count($synonyms) > 0) {
                if ($synonyms[0] !== 'Status: 404') {
                    $pattern = "/\b[1-9][0-9]{1,5}-\d{2}-\d\b/";
                    $casIds = preg_grep($pattern, $synonyms);
                    if ($this->molecule->synonyms) {
                        $_synonyms = $this->molecule->synonyms;
                        $synonyms[] = $_synonyms;
                        $this->molecule->synonyms = $synonyms;
                    } else {
                        $this->molecule->synonyms = $synonyms;
                    }
                    $this->molecule->cas = array_values($casIds);
                    if (! $this->molecule->name && ! empty($synonyms[0])) {
                        $this->molecule->name = $synonyms[0];
                    }
                }
            }
        }
    }
}
