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

        Log::info('ImportPubChem job started for molecule ID: '.$this->molecule->id);

        try {
            $result = $this->fetchIUPACNameFromPubChem();
            if ($result === true) {
                updateCurationStatus($this->molecule->id, $this->stepName, 'completed');
            } else {
                updateCurationStatus($this->molecule->id, $this->stepName, 'failed', 'Failed to fetch or process PubChem data');
                throw new \Exception('Failed to fetch or process PubChem data');
            }
        } catch (\Throwable $e) {
            Log::error("Error processing molecule {$this->molecule->id}: ".$e->getMessage());
            updateCurationStatus($this->molecule->id, $this->stepName, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::info("ImportPubChemAuto failed() method called for molecule {$this->molecule->id}: ".$exception->getMessage());

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
            Log::error('Failed to fetch CID from PubChem for molecule ID: '.$this->molecule->id);
            $this->storeFailedMolecule('failed_cid_fetch');

            return false;
        }

        $cid = $cidResponse->body();
        if ($cid && trim($cid) != '0') {
            $cid = trim(preg_replace('/\s+/', ' ', $cid));
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.$cid.'/json';
            $dataResponse = $this->throttledGet($cidPropsURL);

            if (! $dataResponse->successful()) {
                Log::error('Failed to fetch data from PubChem for CID: '.$cid);
                $this->storeFailedMolecule('failed_data_fetch');

                return false;
            }

            $data = $dataResponse->json();
            if (! isset($data['PC_Compounds'])) {
                Log::error('PC_Compounds key not found for CID: '.$cid);
                $this->storeFailedMolecule('missing_pc_compounds');

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
                Log::error('IUPAC Name not found in PubChem data for CID: '.$cid);
            }

            // Fetch synonyms and CAS numbers
            $this->fetchSynonymsCASFromPubChem($cid);

            if ($IUPACName) {
                $this->molecule->iupac_name = $IUPACName;
            }
            $this->molecule->save();

            return true;
        } else {
            Log::error('Invalid CID from PubChem for molecule ID: '.$this->molecule->id);
            $this->storeFailedMolecule('invalid_cid');

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
                $this->storeFailedMolecule('failed_synonyms_fetch');

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
