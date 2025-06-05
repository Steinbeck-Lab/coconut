<?php

namespace App\Jobs;

use App\Models\Entry;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportEntriesBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ids;

    protected $batch_type;

    /**
     * Create a new job instance.
     */
    public function __construct($ids, ?string $batch_type)
    {
        $this->ids = $ids;
        $this->batch_type = $batch_type;
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

        $entries = Entry::whereIn('id', $this->ids)->get();

        $batchJobs = [];
        foreach ($entries as $entry) {
            if ($this->batch_type == 'auto') {
                array_push($batchJobs, new ImportEntryMoleculeAuto($entry));
            } elseif ($this->batch_type == 'references') {
                array_push($batchJobs, new ImportEntryReferencesAuto($entry));
            } else {
                array_push($batchJobs, new ImportEntry($entry));
            }
        }
        $this->batch()->add($batchJobs);
    }
}
