<?php

namespace App\Console\Commands;

use App\Models\MoleculeOrganism;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixMoleculeOrganismDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:fix-molecule-organism-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate molecule_organism rows by merging collection_ids and citation_ids arrays';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting molecule_organism duplicate fix process...');
        Log::info('Starting molecule_organism duplicate fix process');

        // Step 1: Find all duplicates
        $this->info('Step 1: Finding duplicate records...');

        $duplicates = DB::select('
            SELECT 
                molecule_id,
                organism_id,
                sample_location_id,
                geo_location_id,
                ecosystem_id,
                COUNT(*) as duplicate_count,
                json_agg(id ORDER BY id) as ids
            FROM molecule_organism
            WHERE collection_ids IS NOT NULL
            GROUP BY molecule_id, organism_id, sample_location_id, geo_location_id, ecosystem_id
            HAVING COUNT(*) > 1
        ');

        $totalDuplicateGroups = count($duplicates);

        if ($totalDuplicateGroups === 0) {
            $this->info('✅ No duplicates found! Database is clean.');

            return 0;
        }

        $this->warn("Found {$totalDuplicateGroups} groups of duplicates");

        // Calculate total rows that will be deleted
        $totalRowsToDelete = array_sum(array_map(fn ($d) => $d->duplicate_count - 1, $duplicates));
        $this->warn("Total duplicate rows to be removed: {$totalRowsToDelete}");

        // Step 2: Process each duplicate group
        $this->info('Step 2: Processing duplicates...');
        $progressBar = $this->output->createProgressBar($totalDuplicateGroups);
        $progressBar->start();

        $processedCount = 0;
        $deletedCount = 0;

        DB::transaction(function () use ($duplicates, $progressBar, &$processedCount, &$deletedCount) {
            foreach ($duplicates as $duplicate) {
                $ids = json_decode($duplicate->ids, true);
                $keepId = $ids[0]; // Keep the oldest record
                $deleteIds = array_slice($ids, 1);

                // Use IS NOT DISTINCT FROM for NULL-safe comparison
                $records = DB::select('
                    SELECT * FROM molecule_organism 
                    WHERE molecule_id = ? 
                    AND organism_id = ?
                    AND sample_location_id IS NOT DISTINCT FROM ?
                    AND geo_location_id IS NOT DISTINCT FROM ?
                    AND ecosystem_id IS NOT DISTINCT FROM ?
                ', [
                    $duplicate->molecule_id,
                    $duplicate->organism_id,
                    $duplicate->sample_location_id,
                    $duplicate->geo_location_id,
                    $duplicate->ecosystem_id,
                ]);

                // Merge collection_ids and citation_ids
                $allCollectionIds = [];
                $allCitationIds = [];

                foreach ($records as $record) {
                    $collectionIds = json_decode($record->collection_ids ?? '[]', true);
                    $citationIds = json_decode($record->citation_ids ?? '[]', true);

                    if (is_array($collectionIds)) {
                        $allCollectionIds = array_merge($allCollectionIds, $collectionIds);
                    }
                    if (is_array($citationIds)) {
                        $allCitationIds = array_merge($allCitationIds, $citationIds);
                    }
                }

                // Get unique and sort
                $mergedCollectionIds = array_values(array_unique($allCollectionIds));
                $mergedCitationIds = array_values(array_unique($allCitationIds));
                sort($mergedCollectionIds);
                sort($mergedCitationIds);

                // Update the record we're keeping using Eloquent model for auditing
                $moleculeOrganism = MoleculeOrganism::find($keepId);
                if ($moleculeOrganism) {
                    $moleculeOrganism->collection_ids = $mergedCollectionIds;
                    $moleculeOrganism->citation_ids = $mergedCitationIds;
                    $moleculeOrganism->save(); // This will trigger audit
                }

                // Delete the duplicates using Eloquent model for auditing
                if (! empty($deleteIds)) {
                    MoleculeOrganism::whereIn('id', $deleteIds)->delete(); // This will trigger audit

                    $deletedCount += count($deleteIds);
                }

                $processedCount++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Success summary
        $this->newLine();
        $this->info('✅ Duplicate fix completed successfully!');
        $this->line("   Groups processed: {$processedCount}");
        $this->line("   Rows deleted: {$deletedCount}");

        Log::info('molecule_organism duplicates fixed successfully', [
            'groups_processed' => $processedCount,
            'rows_deleted' => $deletedCount,
        ]);

        return 0;
    }
}
