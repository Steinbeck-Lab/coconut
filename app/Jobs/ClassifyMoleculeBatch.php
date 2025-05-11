<?php

namespace App\Jobs;

use App\Models\Molecule;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyMoleculeBatch implements ShouldQueue
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

        $molecules = Molecule::whereIn('id', $this->ids)
            ->select('id', 'canonical_smiles')
            ->get();

        $batchJobs = [];
        foreach ($molecules as $molecule) {
            array_push($batchJobs, new ClassifyMoleculeAuto($molecule));
        }

        if (! empty($batchJobs)) {
            $this->batch()->add($batchJobs);
        }
    }
}
