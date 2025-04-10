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

        // Generate a unique index name using timestamp to avoid conflicts
        $tempIndexName = 'temp_ref_id_idx_' . time();
        
            // Check if a similar index already exists to avoid errors
            $this->info("Creating temporary index for faster processing...");
            $indexExists = DB::select("
                SELECT 1 
                FROM pg_indexes 
                WHERE tablename = 'entries' 
                AND indexname LIKE 'temp_ref_id_idx_%'
            ");
            
            if (empty($indexExists)) {
                // Create a temporary index to speed up the reference_id lookups
                DB::statement("
                    CREATE INDEX {$tempIndexName} 
                    ON entries (collection_id, reference_id) 
                    WHERE collection_id = {$collectionId}
                ");
                $this->info("Temporary index created: {$tempIndexName}");
            } else {
                $this->info("Using existing temporary index");
            }

        try {
            // Open the CSV file
            $handle = fopen($file, 'r');
            if ($handle === false) {
                $this->error("Failed to open the CSV file.");
                return 1;
            }

            // Read the header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                $this->error("CSV file is empty or invalid.");
                fclose($handle);
                return 1;
            }

            // Trim headers and convert to lowercase for case-insensitive matching
            $headers = array_map(function($header) {
                return strtolower(trim($header));
            }, $headers);
            
            // Find the column indexes for REFERENCE_ID and LINK (case-insensitive)
            $refIdIndex = array_search('reference_id', $headers);
            $linkIndex = array_search('link', $headers);

            if ($refIdIndex === false || $linkIndex === false) {
                $this->error("Required columns 'REFERENCE_ID' and/or 'LINK' not found in CSV.");
                fclose($handle);
                return 1;
            }

            // Read all CSV data and build an update map
            $updateMap = [];
            $lineNumber = 1;
            $skippedCount = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                
                // Skip empty rows or rows that don't have enough columns
                if (empty($row) || count($row) <= $refIdIndex || count($row) <= $linkIndex) {
                    $this->warn("Line {$lineNumber}: Invalid row. Skipping...");
                    $skippedCount++;
                    continue;
                }

                $referenceId = trim($row[$refIdIndex]);
                $newLink = trim($row[$linkIndex]);

                if (empty($referenceId)) {
                    $this->warn("Line {$lineNumber}: Empty reference_id. Skipping...");
                    $skippedCount++;
                    continue;
                }

                // Store the reference_id => link mapping
                $updateMap[$referenceId] = $newLink;
            }

            // Close the file handle
            fclose($handle);
            
            if (empty($updateMap)) {
                $this->error("No valid data found in CSV for updates.");
                return 1;
            }

            // Get all reference_ids from the CSV
            $referenceIds = array_keys($updateMap);
            $totalReferenceIds = count($referenceIds);
            
            $this->info("Processing {$totalReferenceIds} reference IDs from CSV...");
            
            // Set batch size for processing
            $batchSize = 1000; // Adjust based on your memory constraints
            $updatedCount = 0;
            $processedCount = 0;
            
            // Process in batches to avoid memory issues
            foreach (array_chunk($referenceIds, $batchSize) as $batchNumber => $batch) {
                $this->info("Processing batch " . ($batchNumber + 1) . " (" . count($batch) . " items)...");
                
                // Fetch this batch of entries
                $entries = DB::table('entries')
                    ->where('collection_id', $collectionId)
                    ->whereIn('reference_id', $batch)
                    ->select('id', 'reference_id')
                    ->get();
                
                $processedCount += count($batch);
                
                if ($entries->isEmpty()) {
                    $this->warn("No matching entries found in this batch.");
                    continue;
                }
                
                // Use a transaction for each batch
                DB::transaction(function() use ($entries, $updateMap, &$updatedCount) {
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
                
                $this->info("Batch " . ($batchNumber + 1) . " completed. Progress: {$processedCount}/{$totalReferenceIds}");
            }
            
            if ($updatedCount == 0) {
                $this->warn("No matching entries found for any of the provided reference IDs in collection_id: {$collectionId}");
            }
            
            // Calculate not found count
            $notFoundCount = $totalReferenceIds - $updatedCount;
            
            $this->info("Process completed!");
            $this->info("{$updatedCount} entries updated");
            $this->info("{$notFoundCount} reference IDs not found in the specified collection");
            $this->info("{$skippedCount} rows skipped due to invalid data");
            
            // Drop the temporary index if we created one
            if (empty($indexExists)) {
                $this->info("Dropping temporary index...");
                DB::statement("DROP INDEX IF EXISTS {$tempIndexName}");
                $this->info("Temporary index dropped");
            }
            
            return 0;
        } catch (\Exception $e) {
            // Make sure to drop the index even if there's an error
            try {
                if (isset($tempIndexName) && empty($indexExists ?? null)) {
                    $this->warn("Dropping temporary index due to error...");
                    DB::statement("DROP INDEX IF EXISTS {$tempIndexName}");
                }
            } catch (\Exception $indexException) {
                $this->error("Error dropping index: " . $indexException->getMessage());
            }
            
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}