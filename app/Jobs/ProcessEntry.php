<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessEntry implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entry;

    /**
     * Create a new job instance.
     */
    public function __construct($entry)
    {
        $this->entry = $entry;
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

        // Fetch attached reports and update their status to INPROGRESS
        $attachedReports = $this->entry->reports;
        foreach ($attachedReports as $report) {
            $report->status = ReportStatus::INPROGRESS->value;
            $report->save();
        }

        $errors = null;
        $canonical_smiles = $this->entry->canonical_smiles;
        $status = 'SUBMITTED';
        $standardized_smiles = null;
        $parent_canonical_smiles = null;
        $molecular_formula = null;
        $data = null;
        $has_stereocenters = false;
        $error_code = -1;
        $API_URL = env('API_URL', 'https://api.cheminf.studio/latest/');
        $ENDPOINT = $API_URL.'chem/coconut/pre-processing?smiles='.urlencode($canonical_smiles).'&_3d_mol=false&descriptors=false';

        try {
            $response = Http::timeout(600)->get($ENDPOINT);
            if ($response->successful()) {
                $data = $response->json();
                if (array_key_exists('original', $data)) {
                    $errors = $data['original']['errors'];
                    $has_stereocenters = $data['original']['has_stereo'];

                    if ($errors && count($errors) > 0) {
                        foreach ($errors as $error) {
                            if ($error[0] > $error_code) {
                                $error_code = $error[0];
                            }
                        }
                    } else {
                        $error_code = 0;
                    }

                    if ($error_code >= 6) {
                        $status = 'REJECTED';
                        $is_invalid = true;
                    }
                }
                if (array_key_exists('standardized', $data)) {
                    $standardized_smiles = $data['standardized']['representations']['canonical_smiles'];
                    $molecular_formula = preg_split('#/#', $data['parent']['representations']['standard_inchi'])[1];
                }
                if (array_key_exists('parent', $data)) {
                    $parent_canonical_smiles = $data['parent']['representations']['canonical_smiles'];
                }
                $status = 'PASSED';
            } else {
                $statusCode = $response->status();
                $errorData = $response->json();
                $errors = [
                    'Request Exception occurred' => $errorData.' - '.$canonical_smiles,
                    'code' => $statusCode,
                ];
                $status = 'REJECTED';
                $error_code = 7;
                $is_invalid = true;
                Log::error('Request Exception occurred: '.$errorData.' - '.$canonical_smiles, ['code' => $statusCode]);
            }
        } catch (RequestException $e) {
            Log::error('Request Exception occurred: '.$e->getMessage().' - '.$canonical_smiles, ['code' => $e->getCode()]);
            $errors = [
                'Request Exception occurred' => $e->getMessage().' - '.$canonical_smiles,
                'code' => $e->getCode(),
            ];
            $status = 'REJECTED';
            $error_code = 7;
            $is_invalid = true;
            throw $e;
        } catch (\Exception $e) {
            Log::error('An unexpected exception occurred: '.$e->getMessage().' - '.$canonical_smiles);
            $errors = [
                'An unexpected exception occurred' => $e->getMessage().' - '.$canonical_smiles,
            ];
            $status = 'REJECTED';
            $error_code = 7;
            $is_invalid = true;
            throw $e;
        }
        $this->entry->error_code = $error_code;
        $this->entry->errors = $errors;
        $this->entry->standardized_canonical_smiles = $standardized_smiles;
        $this->entry->parent_canonical_smiles = $parent_canonical_smiles;
        $this->entry->molecular_formula = $molecular_formula;
        $this->entry->status = $status;
        $this->entry->cm_data = $data;
        $this->entry->has_stereocenters = $has_stereocenters;
        $this->entry->save();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::info("ProcessEntry failed() method called for entry {$this->entry->id}: ".$exception->getMessage());

        handleJobFailure(
            self::class,
            $exception,
            'process-entry',
            [
                'entry_id' => $this->entry->id,
                'canonical_smiles' => $this->entry->canonical_smiles ?? 'Unknown',
            ],
            $this->batch()?->id,
            null,
            $this->entry->id
        );
    }
}
