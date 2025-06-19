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
     * The step name for this job.
     */
    protected $stepName = 'import-pubchem-names';

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

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
        // Check if the batch has been cancelled
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            $result = $this->fetchIUPACNameFromPubChem();
            if ($result === true) {
                updateCurationStatus($this->molecule->id, $this->stepName, 'completed');
                Log::info('PubChem import completed', [
                    'molecule_id' => $this->molecule->id,
                    'step' => $this->stepName,
                    'result' => 'success',
                ]);
            } else {
                // Treat as data-not-found, not a job failure
                updateCurationStatus($this->molecule->id, $this->stepName, 'failed', 'PubChem data not available');
                Log::warning('PubChem data not available', [
                    'molecule_id' => $this->molecule->id,
                    'step' => $this->stepName,
                    'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
                    'result' => 'data_not_found',
                    'batch_id' => $this->batch()?->id,
                ]);
            }
        } catch (\Throwable $e) {
            // Only actual system errors should be treated as job failures
            updateCurationStatus($this->molecule->id, $this->stepName, 'failed', $e->getMessage());
            Log::error('PubChem import system error', [
                'molecule_id' => $this->molecule->id,
                'step' => $this->stepName,
                'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'batch_id' => $this->batch()?->id,
                'result' => 'system_error',
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     * This should only be called for actual system errors, not data unavailability.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ImportPubChemAuto job system failure', [
            'molecule_id' => $this->molecule->id,
            'step' => $this->stepName,
            'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
            'error_message' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'batch_id' => $this->batch()?->id,
            'result' => 'system_failure',
        ]);

        handleJobFailure(
            self::class,
            $exception,
            $this->stepName,
            [
                'molecule_id' => $this->molecule->id,
                'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
            ],
            $this->batch()?->id,
            $this->molecule->id
        );
    }

    /**
     * Make a throttled HTTP GET request and sleep for 200ms afterward.
     */
    private function throttledGet(string $url)
    {
        $response = Http::get($url);
        usleep(200000); // Sleep for 200 milliseconds to limit to 5 requests per second

        return $response;
    }

    /**
     * Store a failed molecule ID in the JSON file using direct Redis connection
     */
    private function storeFailedMolecule($reason = 'failed_cid_fetch')
    {
        try {
            $lockName = 'pubchem_failed_molecules_lock';
            $lock = Cache::lock($lockName, 30);
            $lockAcquired = false;

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
                $fileExists = Storage::disk('local')->exists($this->failedIdsFile);
                Log::info("File exists check for {$this->failedIdsFile}: ".($fileExists ? 'true' : 'false'));

                if ($fileExists) {
                    $fileContent = Storage::disk('local')->get($this->failedIdsFile);
                    $failedIds = json_decode($fileContent, true);
                    if ($failedIds === null) {
                        Log::error('JSON decode failed: '.json_last_error_msg());
                        $failedIds = [];
                    }
                }

                if (isset($failedIds[$this->molecule->id])) {
                    Log::info("Molecule ID {$this->molecule->id} already exists in failed list, skipping");

                    return;
                }

                $failedIds[$this->molecule->id] = [
                    'molecule_id' => $this->molecule->id,
                    'reason' => $reason,
                    'failed_at' => now()->toDateTimeString(),
                    'smiles' => $this->molecule->canonical_smiles ?? null,
                ];

                $jsonContent = json_encode($failedIds, JSON_PRETTY_PRINT);
                $writeResult = Storage::disk('local')->put($this->failedIdsFile, $jsonContent);

                if ($writeResult) {
                    $verifyContent = Storage::disk('local')->get($this->failedIdsFile);
                    $verifiedIds = json_decode($verifyContent, true);
                    if (isset($verifiedIds[$this->molecule->id])) {
                        Log::info("Verified molecule ID {$this->molecule->id} was successfully added to failed list");
                    } else {
                        Log::error("Verification failed - molecule ID {$this->molecule->id} not found in file after write");
                    }
                }
            } finally {
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
        $smilesURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/smiles/cids/TXT?smiles='.urlencode($this->molecule->canonical_smiles);
        $cidResponse = $this->throttledGet($smilesURL);

        if (! $cidResponse->successful()) {
            Log::debug('PubChem CID fetch failed', [
                'molecule_id' => $this->molecule->id,
                'step' => $this->stepName,
                'failure_point' => 'cid_fetch',
                'http_status' => $cidResponse->status(),
                'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
            ]);

            return false;
        }

        $cid = $cidResponse->body();
        if ($cid && trim($cid) != '0') {
            $cid = trim(preg_replace('/\s+/', ' ', $cid));
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.$cid.'/json';
            $dataResponse = $this->throttledGet($cidPropsURL);

            if (! $dataResponse->successful()) {
                Log::debug('PubChem data fetch failed', [
                    'molecule_id' => $this->molecule->id,
                    'step' => $this->stepName,
                    'failure_point' => 'data_fetch',
                    'cid' => $cid,
                    'http_status' => $dataResponse->status(),
                ]);

                return false;
            }

            $data = $dataResponse->json();
            if (! isset($data['PC_Compounds'])) {
                Log::debug('PubChem response missing compounds', [
                    'molecule_id' => $this->molecule->id,
                    'step' => $this->stepName,
                    'failure_point' => 'missing_pc_compounds',
                    'cid' => $cid,
                ]);

                return false;
            }

            $props = $data['PC_Compounds'][0]['props'] ?? [];
            $IUPACName = null;
            foreach ($props as $prop) {
                if (isset($prop['urn']['label'], $prop['urn']['name'], $prop['value']['sval']) && $prop['urn']['label'] === 'IUPAC Name' && $prop['urn']['name'] === 'Preferred') {
                    $IUPACName = $prop['value']['sval'];
                    break;
                }
            }

            if (! $IUPACName) {
                Log::debug('IUPAC name not found in PubChem data', [
                    'molecule_id' => $this->molecule->id,
                    'step' => $this->stepName,
                    'cid' => $cid,
                    'props_count' => count($props),
                ]);
            }

            // Fetch synonyms and CAS numbers
            $this->fetchSynonymsCASFromPubChem($cid);

            if ($IUPACName) {
                $this->molecule->iupac_name = $IUPACName;
            }
            $this->molecule->save();

            return true;
        } else {
            Log::debug('Invalid CID from PubChem', [
                'molecule_id' => $this->molecule->id,
                'step' => $this->stepName,
                'failure_point' => 'invalid_cid',
                'cid_response' => $cid,
                'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
            ]);

            return false;
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
                Log::debug('PubChem synonyms fetch failed', [
                    'molecule_id' => $this->molecule->id,
                    'step' => $this->stepName,
                    'failure_point' => 'synonyms_fetch',
                    'cid' => $cid,
                    'attempts' => $attempt,
                ]);

                return;
            }

            $data = $synResponse->body();
            $synonyms = preg_split("/\r\n|\n|\r/", $data);
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
