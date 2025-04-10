<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateSourceLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:update-source-links {collection_id} {csv_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update links in entries table for a specific collection_id using data from a CSV file';

    /**
     * @var array Stores information about created temporary indexes
     */
    protected $tempIndexes = [];

    protected $skippedCount = 0;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $collectionId = $this->argument('collection_id');
        $csvName = $this->argument('csv_name');

        $file = storage_path($csvName);
        if (! file_exists($file) || ! is_readable($file)) {
            $this->error('File not found or not readable.');

            return 1;
        }

        $this->info("Updating links for collection_id: {$collectionId} using CSV: {$csvName}");

        try {
            // Create temporary indexes for better performance
            $this->createTempIndex('entries', 'temp_ref_id_idx_'.time(), '(collection_id, reference_id)', "collection_id = {$collectionId}");
            $this->createTempIndex('collection_molecule', 'temp_pivot_ref_idx_'.time(), '(collection_id)', "collection_id = {$collectionId}");

            // Read and parse the CSV file
            $updateMap = $this->parseCSV($file);
            if (empty($updateMap)) {
                $this->dropTempIndexes();

                return 1;
            }

            // Process entries table
            $referenceIds = array_keys($updateMap);
            $totalReferenceIds = count($referenceIds);
            $this->info("Processing {$totalReferenceIds} reference IDs from CSV...");

            $batchSize = 1000; // Adjust based on memory constraints
            $entriesUpdatedCount = $this->updateEntriesTable($collectionId, $referenceIds, $updateMap, $batchSize);

            // Process pivot table
            $pivotUpdatedCount = $this->updatePivotTable($collectionId, $updateMap, $batchSize);

            // Calculate not found count
            $notFoundCount = $totalReferenceIds - $entriesUpdatedCount;

            // Final reporting
            $this->info('Process completed!');
            $this->info("{$entriesUpdatedCount} entries updated");
            $this->info("{$notFoundCount} reference IDs not found in the specified collection");
            $this->info("{$this->skippedCount} rows skipped due to invalid data");
            $this->info("{$pivotUpdatedCount} pivot records updated");

            // Clean up temporary indexes
            $this->dropTempIndexes();

            return 0;
        } catch (\Exception $e) {
            $this->dropTempIndexes();
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Create a temporary index if it doesn't exist
     *
     * @param  string  $table  Table name
     * @param  string  $indexName  Index name
     * @param  string  $columns  Columns to index
     * @param  string  $where  Where clause
     * @return bool
     */
    protected function createTempIndex($table, $indexName, $columns, $where = null)
    {
        $indexExists = DB::select("
            SELECT 1 
            FROM pg_indexes 
            WHERE tablename = '{$table}' 
            AND indexname LIKE '".substr($indexName, 0, strrpos($indexName, '_'))."_%'
        ");

        if (empty($indexExists)) {
            $sql = "CREATE INDEX {$indexName} ON {$table} {$columns}";
            if ($where) {
                $sql .= " WHERE {$where}";
            }

            DB::statement($sql);
            $this->info("Temporary index created for {$table}: {$indexName}");

            // Store information about the created index
            $this->tempIndexes[] = [
                'table' => $table,
                'name' => $indexName,
                'exists' => false,
            ];

            return true;
        } else {
            $this->info("Using existing temporary index for {$table}");

            // Store information about the existing index
            $this->tempIndexes[] = [
                'table' => $table,
                'name' => $indexName,
                'exists' => true,
            ];

            return false;
        }
    }

    /**
     * Drop all temporary indexes created during the process
     */
    protected function dropTempIndexes()
    {
        try {
            foreach ($this->tempIndexes as $index) {
                if (! $index['exists']) {
                    $this->info("Dropping temporary index for {$index['table']}...");
                    DB::statement("DROP INDEX IF EXISTS {$index['name']}");
                }
            }
        } catch (\Exception $e) {
            $this->error('Error dropping indexes: '.$e->getMessage());
        }
    }

    /**
     * Parse the CSV file and build a reference_id => link mapping
     *
     * @param  string  $file  Path to CSV file
     * @return array
     */
    protected function parseCSV($file)
    {
        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error('Failed to open the CSV file.');

            return [];
        }

        // Read the header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $this->error('CSV file is empty or invalid.');
            fclose($handle);

            return [];
        }

        // Trim headers and convert to lowercase for case-insensitive matching
        $headers = array_map(function ($header) {
            return strtolower(trim($header));
        }, $headers);

        // Find the column indexes for REFERENCE_ID and LINK (case-insensitive)
        $refIdIndex = array_search('reference_id', $headers);
        $linkIndex = array_search('link', $headers);

        if ($refIdIndex === false || $linkIndex === false) {
            $this->error("Required columns 'REFERENCE_ID' and/or 'LINK' not found in CSV.");
            fclose($handle);

            return [];
        }

        // Read all CSV data and build an update map
        $updateMap = [];
        $lineNumber = 1;
        $this->skippedCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // Skip invalid rows
            if (empty($row) || count($row) <= $refIdIndex || count($row) <= $linkIndex) {
                $this->warn("Line {$lineNumber}: Invalid row. Skipping...");
                $this->skippedCount++;

                continue;
            }

            $referenceId = trim($row[$refIdIndex]);
            $newLink = trim($row[$linkIndex]);

            if (empty($referenceId)) {
                $this->warn("Line {$lineNumber}: Empty reference_id. Skipping...");
                $this->skippedCount++;

                continue;
            }

            // Store the reference_id => link mapping
            $updateMap[$referenceId] = $newLink;
        }

        fclose($handle);

        if (empty($updateMap)) {
            $this->error('No valid data found in CSV for updates.');
        }

        return $updateMap;
    }

    /**
     * Update the entries table in batches
     *
     * @param  int  $collectionId  The collection ID
     * @param  array  $referenceIds  List of reference IDs
     * @param  array  $updateMap  Reference ID => Link mapping
     * @param  int  $batchSize  Size of each batch
     * @return int Number of updated records
     */
    protected function updateEntriesTable($collectionId, $referenceIds, $updateMap, $batchSize)
    {
        $updatedCount = 0;
        $processedCount = 0;
        $totalReferenceIds = count($referenceIds);

        // Process in batches to avoid memory issues
        foreach (array_chunk($referenceIds, $batchSize) as $batchNumber => $batch) {
            $this->info('Processing entries batch '.($batchNumber + 1).' ('.count($batch).' items)...');

            // Fetch this batch of entries
            $entries = DB::table('entries')
                ->where('collection_id', $collectionId)
                ->whereIn('reference_id', $batch)
                ->select('id', 'reference_id')
                ->get();

            $processedCount += count($batch);

            if ($entries->isEmpty()) {
                $this->warn('No matching entries found in this batch.');

                continue;
            }

            // Use a transaction for each batch
            DB::transaction(function () use ($entries, $updateMap, &$updatedCount) {
                foreach ($entries as $entry) {
                    if (isset($updateMap[$entry->reference_id])) {
                        DB::table('entries')
                            ->where('id', $entry->id)
                            ->update(['link' => $updateMap[$entry->reference_id]]);
                        $updatedCount++;
                    }
                }
            });

            // Free up memory
            unset($entries);

            $this->info('Entries batch '.($batchNumber + 1)." completed. Progress: {$processedCount}/{$totalReferenceIds}");
        }

        return $updatedCount;
    }

    /**
     * Update the collection_molecule pivot table in batches
     *
     * @param  int  $collectionId  The collection ID
     * @param  array  $updateMap  Reference ID => Link mapping
     * @param  int  $batchSize  Size of each batch
     * @return int Number of updated records
     */
    protected function updatePivotTable($collectionId, $updateMap, $batchSize)
    {
        $this->info('Updating URLs in the collection_molecule pivot table...');

        // Get all pivot records for this collection
        $pivotRecords = DB::table('collection_molecule')
            ->where('collection_id', $collectionId)
            ->whereNotNull('reference')
            ->select('id', 'reference')
            ->get();

        $pivotUpdatedCount = 0;
        $pivotTotalCount = count($pivotRecords);

        $this->info("Found {$pivotTotalCount} pivot records with references to update");

        if ($pivotTotalCount === 0) {
            return 0;
        }

        // Process pivot records in batches
        foreach (array_chunk($pivotRecords->toArray(), $batchSize) as $batchNumber => $pivotBatch) {
            $this->info('Processing pivot batch '.($batchNumber + 1).' ('.count($pivotBatch).' items)...');

            DB::transaction(function () use ($pivotBatch, $updateMap, &$pivotUpdatedCount) {
                foreach ($pivotBatch as $pivot) {
                    // Skip if reference is empty
                    if (empty($pivot->reference)) {
                        continue;
                    }

                    // Split reference IDs by pipe character
                    $referenceIds = array_map('trim', explode('|', $pivot->reference));
                    $urls = [];

                    // Build the corresponding URL string with the same structure
                    foreach ($referenceIds as $refId) {
                        if (isset($updateMap[$refId])) {
                            $urls[] = $updateMap[$refId];
                        } else {
                            // Keep the position even if we don't have a URL
                            $urls[] = '';
                        }
                    }

                    // Only update if we have at least one URL
                    if (! empty(array_filter($urls))) {
                        $urlString = implode('|', $urls);

                        DB::table('collection_molecule')
                            ->where('id', $pivot->id)
                            ->update(['url' => $urlString]);

                        $pivotUpdatedCount++;
                    }
                }
            });

            $this->info('Pivot batch '.($batchNumber + 1).' completed.');
        }

        $this->info("Pivot table update completed! {$pivotUpdatedCount} pivot records updated");

        return $pivotUpdatedCount;
    }
}
