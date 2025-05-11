<?php

namespace App\Jobs;

use App\Models\Properties;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassifyMoleculeAuto implements ShouldQueue
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
        if ($this->batch()->cancelled()) {
            return;
        }

        $id = $this->molecule->id;
        $canonical_smiles = $this->molecule->canonical_smiles;

        // Build endpoint
        $apiUrl = 'https://npclassifier.gnps2.org/classify?smiles=';
        $endpoint = $apiUrl.urlencode($canonical_smiles);

        // Fetch classification data from API
        $response_data = $this->fetchFromApi($endpoint, $canonical_smiles);

        if ($response_data) {
            $this->updateProperties($id, $response_data);
        }
    }

    /**
     * Make an HTTP GET request with basic retry/backoff handling.
     *
     * @return mixed (array|null) Returns the JSON-decoded response or null on failure.
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
                    return $response->json();
                }

                // Throttling: if we hit a 429, wait and retry
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
     * Update molecule properties with classification data.
     */
    private function updateProperties(int $moleculeId, array $data): void
    {
        $properties = Properties::where('molecule_id', $moleculeId)->whereNull('np_classifier_pathway')->first();

        if ($properties) {
            $properties['np_classifier_pathway'] = (isset($data['pathway_results'][0]) && ! empty($data['pathway_results'][0]))
                ? $data['pathway_results'][0]
                : null;

            $properties['np_classifier_superclass'] = (isset($data['superclass_results'][0]) && ! empty($data['superclass_results'][0]))
                ? $data['superclass_results'][0]
                : null;

            $properties['np_classifier_class'] = (isset($data['class_results'][0]) && ! empty($data['class_results'][0]))
                ? $data['class_results'][0]
                : null;

            $properties['np_classifier_is_glycoside'] = (isset($data['isglycoside']) && $data['isglycoside'] !== '')
                ? filter_var($data['isglycoside'], FILTER_VALIDATE_BOOLEAN)
                : null;

            $properties->save();
        }
    }
}
