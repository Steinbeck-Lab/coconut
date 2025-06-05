<?php

namespace App\Jobs;

use App\Models\Molecule;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateCoordinatesBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ids;

    /**
     * Create a new job instance.
     */
    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        // Fetch the molecules for this batch
        $molecules = Molecule::whereIn('id', $this->ids)
            ->select('id', 'canonical_smiles')
            ->get();

        if ($molecules->isEmpty()) {
            Log::info('No molecules found for the provided IDs in batch');

            return;
        }

        // Create individual jobs for each molecule
        $batchJobs = [];
        foreach ($molecules as $molecule) {
            // array_push($batchJobs, new GenerateCoordinatesAuto($molecule));
            $delay = 5;

            $job = (new GenerateCoordinatesAuto($molecule))
                ->delay(now()->addSeconds($delay));
            array_push($batchJobs, $job);
        }

        if (! empty($batchJobs)) {
            $this->batch()->add($batchJobs);
            Log::info('Added '.count($batchJobs).' coordinate generation jobs to batch');
        }
    }
}
