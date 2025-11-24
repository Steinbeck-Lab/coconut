<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Events\PrePublishJobFailed;
use App\Models\Entry;
use App\Services\CmsClient;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEntryBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entry_ids;

    protected $batch_no;

    protected $failedEntries = [];

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 3600; // 1 hour timeout for batch processing

    /**
     * Create a new job instance.
     */
    public function __construct($entry_ids, $batch_no)
    {
        $this->entry_ids = $entry_ids;
        $this->batch_no = $batch_no;

        // Ensure this job runs on the import queue
        $this->onQueue('import');
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

        // Get entries that need processing
        $entries = Entry::whereIn('id', $this->entry_ids)
            ->where('status', 'SUBMITTED')
            ->whereNull('molecule_id')
            ->get();

        if ($entries->count() == 0) {
            Log::info("Batch {$this->batch_no}: No entries need processing from provided ".count($this->entry_ids).' entry IDs');

            return;
        }

        Log::info("Batch {$this->batch_no}: ProcessEntryBatch processing ".count($this->entry_ids).' entry IDs');
        Log::info("Batch {$this->batch_no}: Processing {$entries->count()} entries for validation");

        $successCount = 0;
        $failedCount = 0;

        foreach ($entries as $entry) {
            try {
                $this->processEntry($entry);
                $successCount++;
            } catch (Exception $e) {
                $failedCount++;
                $this->failedEntries[] = [
                    'entry_id' => $entry->id,
                    'canonical_smiles' => $entry->canonical_smiles ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
                Log::error("Batch {$this->batch_no}: ProcessEntryBatch job failed for entry {$entry->id}: ".$e->getMessage());
            }
        }

        Log::info("Batch {$this->batch_no}: ProcessEntryBatch completed: {$successCount} successful, {$failedCount} failed out of ".count($this->entry_ids).' total entry IDs');

        if (! empty($this->failedEntries)) {
            $this->entry_ids = array_map(function ($entry) {
                return $entry['entry_id'];
            }, $this->failedEntries);
            Log::info("Batch {$this->batch_no}: Retrying Failed Entries: ".count($this->failedEntries));
            $this->failedEntries = []; // Clear failed entries to prevent infinite loop
            $this->handle();
        }
    }

    /**
     * Process entry with logic exactly matching ProcessEntry
     */
    public function processEntry(Entry $entry): void
    {
        // Fetch attached reports and update their status to INPROGRESS
        $attachedReports = $entry->reports;
        foreach ($attachedReports as $report) {
            $report->status = ReportStatus::INPROGRESS->value;
            $report->save();
        }

        $errors = null;
        $canonical_smiles = $entry->canonical_smiles;
        $status = 'SUBMITTED';
        $standardized_smiles = null;
        $parent_canonical_smiles = null;
        $molecular_formula = null;
        $data = null;
        $has_stereocenters = false;
        $is_cis_trans = false;
        $is_invalid = false;
        $error_code = -1;

        $cmsClient = app(CmsClient::class);

        try {
            $response = $cmsClient->get('chem/coconut/pre-processing', [
                'smiles' => $canonical_smiles,
                '_3d_mol' => 'false',
                'descriptors' => 'false',
            ], false);

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
                    $is_cis_trans = $data['standardized']['has_stereogenic_elements'];
                }
                if (array_key_exists('parent', $data)) {
                    $parent_canonical_smiles = $data['parent']['representations']['canonical_smiles'];
                }
                if ($status != 'REJECTED' && $error_code < 6) {
                    $status = 'PASSED';
                }
            } else {
                $statusCode = $response->status();
                $errorData = $response->json();
                $errorMessage = is_array($errorData) ? json_encode($errorData) : (string) $errorData;
                $errors = [
                    'Request Exception occurred' => $errorMessage.' - '.$canonical_smiles,
                    'code' => $statusCode,
                ];
                $status = 'REJECTED';
                $error_code = 7;
                $is_invalid = true;
                Log::error("Batch {$this->batch_no}: Request Exception occurred: ".$errorMessage.' - '.$canonical_smiles, ['code' => $statusCode]);
            }
        } catch (RequestException $e) {
            Log::error("Batch {$this->batch_no}: Request Exception occurred: ".$e->getMessage().' - '.$canonical_smiles, ['code' => $e->getCode()]);
            $errors = [
                'Request Exception occurred' => $e->getMessage().' - '.$canonical_smiles,
                'code' => $e->getCode(),
            ];
            $status = 'REJECTED';
            $error_code = 7;
            $is_invalid = true;
            throw $e;
        } catch (\Exception $e) {
            Log::error("Batch {$this->batch_no}: An unexpected exception occurred: ".$e->getMessage().' - '.$canonical_smiles);
            $errors = [
                'An unexpected exception occurred' => $e->getMessage().' - '.$canonical_smiles,
            ];
            $status = 'REJECTED';
            $error_code = 7;
            $is_invalid = true;
            throw $e;
        }

        $entry->error_code = $error_code;
        $entry->errors = $errors;
        $entry->standardized_canonical_smiles = $standardized_smiles;
        $entry->parent_canonical_smiles = $parent_canonical_smiles;
        $entry->is_invalid = $is_invalid;
        $entry->molecular_formula = $molecular_formula;
        $entry->status = $status;
        $entry->cm_data = $data;
        $entry->has_stereocenters = $has_stereocenters;
        $entry->is_cis_trans = $is_cis_trans;
        $entry->save();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::info('ProcessEntryBatch failed() method called: '.$exception->getMessage());

        handleJobFailure(
            self::class,
            $exception,
            'process-entry-batch',
            [
                'entry_ids' => $this->entry_ids,
                'total_entries' => count($this->entry_ids),
                'failed_entries' => $this->failedEntries,
            ],
            $this->batch()?->id,
            null,
            null,
            PrePublishJobFailed::class
        );
    }
}
