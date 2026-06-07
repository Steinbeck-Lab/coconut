<?php

namespace App\Services\CollectionVersioning;

use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ValidateMoleculesBatchWaiter
{
    public function runAndWait(Collection $collection, int $maxWaitSeconds = 3600): void
    {
        Artisan::call('coconut:validate-molecules', ['collection_id' => $collection->id]);

        $batch = $this->findLatestBatchForCollection($collection->id);
        if (! $batch) {
            $this->assertGateConditions($collection);

            return;
        }

        $start = time();
        while (! $batch->finished()) {
            if ((time() - $start) > $maxWaitSeconds) {
                throw new RuntimeException("Validation batch timed out for collection {$collection->id}.");
            }
            sleep(15);
            $batch = Bus::findBatch($batch->id) ?? $batch;
        }

        if ($batch->hasFailures()) {
            throw new RuntimeException("Validation batch failed for collection {$collection->id}: {$batch->failedJobs} jobs failed.");
        }

        $this->assertGateConditions($collection->fresh());
    }

    protected function findLatestBatchForCollection(int $collectionId): ?Batch
    {
        $record = DB::table('job_batches')
            ->where('name', 'Validate Molecules '.$collectionId)
            ->orderByDesc('created_at')
            ->first();

        if (! $record) {
            return null;
        }

        return Bus::findBatch($record->id);
    }

    protected function assertGateConditions(Collection $collection): void
    {
        $submitted = Entry::query()
            ->where('collection_id', $collection->id)
            ->where('status', 'SUBMITTED')
            ->count();

        if ($submitted > 0) {
            throw new RuntimeException("Collection {$collection->id} still has {$submitted} SUBMITTED entries after validation.");
        }

        $missingSmiles = Entry::query()
            ->where('collection_id', $collection->id)
            ->where('status', 'PASSED')
            ->whereNull('standardized_canonical_smiles')
            ->count();

        if ($missingSmiles > 0) {
            throw new RuntimeException("Collection {$collection->id} has {$missingSmiles} PASSED entries without standardized_canonical_smiles.");
        }
    }
}
