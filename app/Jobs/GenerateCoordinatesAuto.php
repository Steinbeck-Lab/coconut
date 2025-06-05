<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\Structure;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateCoordinatesAuto implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 45;

    /**
     * Create a new job instance.
     */
    public function __construct($molecule)
    {
        $this->molecule = $molecule;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        try {
            $id = $this->molecule->id;
            $canonical_smiles = $this->molecule->canonical_smiles;

            // Build endpoints
            $apiUrl = env('API_URL', 'https://api.cheminf.studio/latest/');
            $d2Endpoint = $apiUrl . 'convert/mol2D?smiles=' . urlencode($canonical_smiles) . '&toolkit=rdkit';
            $d3Endpoint = $apiUrl . 'convert/mol3D?smiles=' . urlencode($canonical_smiles) . '&toolkit=rdkit';

            // Fetch coordinates from API
            $d2 = $this->fetchFromApi($d2Endpoint, $canonical_smiles);
            $d3 = $this->fetchFromApi($d3Endpoint, $canonical_smiles);

            // Skip if both 2D and 3D coordinates are null
            if ((is_null($d2) || $d2 === '') && (is_null($d3) || $d3 === '')) {
                $error = "Failed to generate coordinates for molecule ID: {$id}";
                Log::warning($error);
                updateCurationStatus($id, 'generate-coordinates', 'failed', $error);

                return;
            }

            // Save the structure
            $this->saveStructure($id, $d2, $d3);
            updateCurationStatus($id, 'generate-coordinates', 'completed');

            // Update attached reports status to COMPLETED
            foreach ($this->molecule->reports as $report) {
                $report->status = ReportStatus::COMPLETED->value;
                $report->save();
            }
        } catch (\Exception $e) {
            $error = "Error processing molecule {$this->molecule->id}: " . $e->getMessage();
            Log::error($error);
            updateCurationStatus($this->molecule->id, 'generate-coordinates', 'failed', $error);

            // Dispatch event for job-level notification
            \App\Events\ImportPipelineJobFailed::dispatch(
                self::class,
                $e,
                [
                    'molecule_id' => $this->molecule->id,
                    'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
                    'step' => 'generate-coordinates',
                ],
                $this->batch()?->id
            );

            throw $e;
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('999999'))
                ->releaseAfter(30)      // Release lock if job fails/times out
                ->expireAfter(180)      // Maximum lock duration
                ->dontRelease()         // Don't retry if can't acquire lock
                ->shared()
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateCoordinatesAuto job failed for molecule ID ' . $this->molecule->id . ': ' . $exception->getMessage());

        // Check if this is a timeout exception
        $isTimeout = str_contains($exception->getMessage(), 'timeout') ||
            str_contains($exception->getMessage(), 'timed out') ||
            $exception instanceof \Illuminate\Queue\MaxAttemptsExceededException;

        $errorMessage = $isTimeout ?
            'Job timed out after 45 seconds' :
            $exception->getMessage();

        updateCurationStatus($this->molecule->id, 'generate-coordinates', 'failed', $errorMessage);

        // Dispatch event for notification handling
        \App\Events\ImportPipelineJobFailed::dispatch(
            self::class,
            $exception,
            [
                'molecule_id' => $this->molecule->id,
                'canonical_smiles' => $this->molecule->canonical_smiles ?? 'Unknown',
                'step' => 'generate-coordinates',
                'timeout' => $isTimeout,
            ],
            $this->batch()?->id
        );
    }

    /**
     * Make an HTTP GET request with basic retry/backoff handling.
     */
    private function fetchFromApi(string $endpoint, string $smiles)
    {
        $maxRetries = 3;
        $backoffSeconds = 2;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $response = Http::timeout(30)->get($endpoint);

                if ($response->successful()) {
                    return $response->body();
                }

                // Throttling: if we hit a 429, wait and retry
                if ($response->status() === 429) {
                    Log::warning("Throttled (429) for SMILES: {$smiles}. Retrying in {$backoffSeconds} second(s)...");
                    sleep($backoffSeconds);
                    $attempt++;

                    continue;
                }

                Log::error("Error fetching data for SMILES: {$smiles}, HTTP status: " . $response->status());

                return null;
            } catch (\Exception $e) {
                Log::error("Exception fetching data for SMILES: {$smiles}. " . $e->getMessage());
                sleep($backoffSeconds);
                $attempt++;
            }
        }

        return null;
    }

    /**
     * Save structure data to the database.
     */
    private function saveStructure(int $moleculeId, $d2, $d3): void
    {
        try {
            $structure = new Structure;
            $structure->molecule_id = $moleculeId;
            $structure['2d'] = json_encode($d2);
            $structure['3d'] = json_encode($d3);
            $structure->save();
        } catch (\Throwable $e) {
            Log::error("Error saving structure for molecule ID {$moleculeId}: " . $e->getMessage());
            throw $e;
        }
    }
}
