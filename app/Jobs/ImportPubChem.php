<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ImportPubChem implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

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

    public function fetchIUPACNameFromPubChem()
    {
        $smilesURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/smiles/cids/TXT?smiles='
            .urlencode($this->molecule->canonical_smiles);
        $cidResponse = $this->throttledGet($smilesURL);

        if (! $cidResponse->successful()) {
            return;
        }

        $cid = $cidResponse->body();

        if ($cid && trim($cid) != '0') {
            $cid = trim(preg_replace('/\s+/', ' ', $cid));
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.$cid.'/json';
            $dataResponse = $this->throttledGet($cidPropsURL);

            if (! $dataResponse->successful()) {
                return;
            }

            $data = $dataResponse->json();

            // Check if the key exists before proceeding.
            if (! isset($data['PC_Compounds'])) {
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
                }
            }

            // Fetch synonyms and CAS numbers.
            $this->fetchSynonymsCASFromPubChem($cid);

            if ($IUPACName) {
                $this->molecule->iupac_name = $IUPACName;
            }
            $this->molecule->save();
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
