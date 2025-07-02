<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportPubChemAuto implements ShouldBeUnique, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

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

    public function fetchIUPACNameFromPubChem()
    {
        $smilesURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/smiles/cids/TXT?smiles='.urlencode($this->molecule->canonical_smiles);
        $cidResponse = $this->throttledGet($smilesURL);

        if (! $cidResponse->successful()) {
            updateCurationStatus($this->molecule->id, $this->stepName, 'failed', 'PubChem CID fetch failed - HTTP status: '.$cidResponse->status());

            return false;
        }

        $cid = $cidResponse->body();
        if ($cid && trim($cid) != '0') {
            $cid = trim(preg_replace('/\s+/', ' ', $cid));
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.$cid.'/json';
            $dataResponse = $this->throttledGet($cidPropsURL);

            if (! $dataResponse->successful()) {
                updateCurationStatus($this->molecule->id, $this->stepName, 'failed', 'PubChem data fetch failed for CID: '.$cid.' - HTTP status: '.$dataResponse->status());

                return false;
            }

            $data = $dataResponse->json();
            if (! isset($data['PC_Compounds'])) {
                updateCurationStatus($this->molecule->id, $this->stepName, 'failed', 'PubChem response missing compounds for CID: '.$cid);

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
                updateCurationStatus($this->molecule->id, $this->stepName, 'partial', 'IUPAC name not found in PubChem data for CID: '.$cid);
            }

            // Fetch synonyms and CAS numbers
            $this->fetchSynonymsCASFromPubChem($cid);

            if ($IUPACName) {
                $this->molecule->iupac_name = $IUPACName;
            }
            $this->molecule->save();

            return true;
        } else {
            updateCurationStatus($this->molecule->id, $this->stepName, 'failed', 'Invalid CID from PubChem: '.$cid);

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
                updateCurationStatus($this->molecule->id, $this->stepName, 'partial', 'PubChem synonyms fetch failed for CID: '.$cid.' after '.$attempt.' attempts');

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
