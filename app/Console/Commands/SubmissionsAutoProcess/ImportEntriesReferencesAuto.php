<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportEntriesReferencesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:enrich-molecules {collection_id : The ID of the collection to import references for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import references and organism details for entries in AUTOCURATION status';

    /**
     * Configuration variables for easy tuning
     * Can be overridden via environment variables:
     * - ENRICHMENT_BATCH_SIZE (default: 1500)
     * - ENRICHMENT_INDEX_THRESHOLD (default: 1000)
     */
    private $batchSize = 1500;                           // Number of entries per batch job (affects memory usage and parallelism)

    private $indexCreationThreshold = 1000;             // Minimum entries to create performance indexes (balance overhead vs benefit)

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');

        $collection = DB::selectOne('SELECT * FROM collections WHERE id = ?', [$collection_id]);
        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }
        Log::info("Importing references for collection ID: {$collection_id}");

        // Update collection status
        DB::update(
            'UPDATE collections SET jobs_status = ?, job_info = ?, updated_at = ? WHERE id = ?',
            ['PROCESSING', 'Importing references: Citations and Organism Info', now(), $collection_id]
        );

        // Build the query to get entries in AUTOCURATION status with active molecules
        $sql = '
            SELECT e.id 
            FROM entries e 
            WHERE e.status = ? 
            AND e.molecule_id IS NOT NULL 
            AND e.collection_id = ? 
            ORDER BY e.id
        ';
        $params = ['AUTOCURATION', $collection_id];

        $entries = DB::select($sql, $params);
        $entryIds = array_column($entries, 'id');
        $totalCount = count($entryIds);

        Log::info("Found {$totalCount} entries to process for enrichment in collection ID: {$collection_id}");
        Log::info("Configuration: batch_size={$this->batchSize}, index_threshold={$this->indexCreationThreshold}");

        if ($totalCount === 0) {
            Log::info("No entries found in AUTOCURATION status for collection ID {$collection_id}.");
            DB::update(
                'UPDATE collections SET jobs_status = ?, job_info = ?, updated_at = ? WHERE id = ?',
                ['COMPLETE', '', now(), $collection_id]
            );
        }

        // Create performance indexes for larger batches
        if ($totalCount > $this->indexCreationThreshold) {
            $this->createPerformanceIndexes();
        }

        // Split entry IDs into chunks and process each batch directly in the command
        $entryIdChunks = array_chunk($entryIds, $this->batchSize);
        $totalBatches = count($entryIdChunks);

        $this->info("Processing {$totalCount} entries in {$totalBatches} batches of up to {$this->batchSize} entries each");

        $overallSuccessCount = 0;
        $overallFailedCount = 0;
        $overallAuditRecords = [];
        $overallStartTime = microtime(true);

        // Create progress bar for batches
        $batchProgressBar = $this->output->createProgressBar($totalBatches);
        $batchProgressBar->setFormat(' %current%/%max% batches [%bar%] %percent%% %elapsed%/%estimated% %memory%');
        $batchProgressBar->start();

        foreach ($entryIdChunks as $batchIndex => $entryIdBatch) {
            $batchNo = $batchIndex + 1;
            $batchStartTime = microtime(true);

            $batchResult = $this->processBatch($entryIdBatch, $batchNo, $totalBatches);

            $overallSuccessCount += $batchResult['successCount'];
            $overallFailedCount += $batchResult['failedCount'];
            $overallAuditRecords = array_merge($overallAuditRecords, $batchResult['auditRecords']);

            $batchDuration = round(microtime(true) - $batchStartTime, 2);

            // Advance progress bar
            $batchProgressBar->advance();

            // Log detailed batch results to log file only
            Log::info("Batch {$batchNo}/{$totalBatches} completed: {$batchResult['successCount']} successful, {$batchResult['failedCount']} failed (Duration: {$batchDuration}s)");
        }

        $batchProgressBar->finish();
        $this->newLine();

        $overallDuration = round(microtime(true) - $overallStartTime, 2);

        // Display final summary
        $this->info('Enrichment completed successfully!');
        $this->line("Total entries processed: {$totalCount}");
        $this->line("Successful: {$overallSuccessCount}");
        $this->line("Failed: {$overallFailedCount}");
        $this->line("Total duration: {$overallDuration}s");

        // Log detailed results
        Log::info("All batches completed: {$overallSuccessCount} successful, {$overallFailedCount} failed out of {$totalCount} entries (Total Duration: {$overallDuration}s)");

        // Bulk insert all audit records
        $this->insertAuditRecords($overallAuditRecords);

        // Clean up indexes if they were created
        if ($totalCount > $this->indexCreationThreshold) {
            try {
                $this->dropPerformanceIndexes();
            } catch (\Exception $e) {
                Log::error('Failed to drop performance indexes: '.$e->getMessage());
            }
        }

        DB::update(
            'UPDATE collections SET jobs_status = ?, job_info = ?, updated_at = ? WHERE id = ?',
            ['COMPLETE', '', now(), $collection_id]
        );

        Log::info("References import process completed for collection ID {$collection_id}.");
    }

    /**
     * Create temporary performance indexes for enrichment optimization
     */
    private function createPerformanceIndexes(): void
    {
        Log::info('Creating temporary performance indexes for enrichment optimization based on EnrichMoleculesBatch queries');

        $indexes = [

            // Citation operations - text lookups with lockForUpdate
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_citations_text ON citations(citation_text)',

            // Citables polymorphic relationship table for syncWithoutDetaching
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_citables_molecule ON citables(citation_id, citable_type, citable_id) WHERE citable_type = \'App\\Models\\Molecule\'',
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
                Log::debug('Created enrichment performance index');
            } catch (\Exception $e) {
                Log::debug('Index creation skipped (may already exist): '.$e->getMessage());
            }
        }

        Log::info('Enrichment performance indexes created successfully');
    }

    /**
     * Drop performance indexes (non-static version)
     */
    private function dropPerformanceIndexes(): void
    {
        Log::info('Dropping temporary enrichment performance indexes after processing completion');

        $indexes = [
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_citations_text',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_citables_molecule',
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
                Log::debug('Dropped enrichment performance index');
            } catch (\Exception $e) {
                Log::debug('Index drop failed (may not exist): '.$e->getMessage());
            }
        }

        Log::info('Enrichment performance indexes cleaned up successfully');
    }

    /**
     * Process a batch of entry IDs
     */
    private function processBatch(array $entryIds, int $batchNo, int $totalBatches): array
    {
        $successCount = 0;
        $failedCount = 0;
        $auditRecords = [];

        // Get the full entry objects for this batch
        $placeholders = str_repeat('?,', count($entryIds) - 1).'?';
        $entries = DB::select(
            "SELECT * FROM entries WHERE id IN ({$placeholders}) AND status = ? AND molecule_id IS NOT NULL",
            array_merge($entryIds, ['AUTOCURATION'])
        );

        if (empty($entries)) {
            Log::info("Batch {$batchNo}/{$totalBatches}: No entries need enrichment processing");

            return [
                'successCount' => 0,
                'failedCount' => 0,
                'auditRecords' => [],
            ];
        }

        // Fetch molecules for this batch
        $moleculeIds = array_values(array_unique(array_column($entries, 'molecule_id')));
        $molecules = [];

        if (! empty($moleculeIds)) {
            $moleculePlaceholders = str_repeat('?,', count($moleculeIds) - 1).'?';
            $moleculeResults = DB::select(
                "SELECT * FROM molecules WHERE id IN ({$moleculePlaceholders})",
                $moleculeIds
            );
            // Index molecules by ID for quick lookup
            foreach ($moleculeResults as $molecule) {
                $molecules[$molecule->id] = $molecule;
            }
        }

        // Process each entry in the batch
        foreach ($entries as $index => $entry) {
            try {

                $entryAuditRecords = []; // Collect audit records for this entry
                DB::transaction(function () use ($entry, $molecules, &$entryAuditRecords) {
                    $this->processEntryReferences($entry, $molecules, $entryAuditRecords);
                });

                // Add audit records from successful transaction
                $auditRecords = array_merge($auditRecords, $entryAuditRecords);
                $successCount++;

            } catch (\Throwable $e) {
                Log::error("Batch {$batchNo}/{$totalBatches}: Failed to enrich entry {$entry->id}: ".$e->getMessage());
                $failedCount++;
            }
        }

        return [
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'auditRecords' => $auditRecords,
        ];
    }

    /**
     * Process entry references (moved from EnrichMoleculesBatch)
     */
    public function processEntryReferences(\stdClass $entry, array $molecules, array &$entryAuditRecords = []): void
    {
        // Only process entries that are in AUTOCURATION status and have a molecule_id
        if ($entry->status !== 'AUTOCURATION' || ! $entry->molecule_id) {
            return;
        }

        // Get the molecule from the pre-fetched array
        $molecule = $molecules[$entry->molecule_id] ?? null;
        if (! $molecule) {
            return;
        }

        $citation_ids_array = [];

        // Decode meta_data JSON string to array for stdClass entry
        $meta_data = is_string($entry->meta_data) ? json_decode($entry->meta_data, true) : (array) $entry->meta_data;

        // Process references if they exist
        if (isset($meta_data['new_molecule_data']['references'])) {
            // Process each reference
            $this->processReferences($meta_data['new_molecule_data']['references'], $molecule, $entry, $citation_ids_array, $entryAuditRecords);
        }

        // Mark as completed
        updateCurationStatus($molecule->id, 'enrich-molecules', 'completed');

        // Update the entry status to IMPORTED and collect audit
        $oldStatus = $entry->status;
        DB::table('entries')->where('id', $entry->id)->update(['status' => 'IMPORTED']);
        $this->collectAudit('entries', $entry->id, ['status' => $oldStatus], ['status' => 'IMPORTED'], $entryAuditRecords);
    }

    /**
     * Process references for the molecule (moved from EnrichMoleculesBatch)
     */
    public function processReferences($references, \stdClass $molecule, \stdClass $entry, array &$citation_ids_array, array &$entryAuditRecords = []): void
    {
        // Save organism details - sample location and geo location
        foreach ($references as $reference) {
            // Create separate citation array for this specific reference
            $currentReferenceCitations = [];

            $doi = $reference['doi'] ?? '';

            $doiRegex = '/\b(10[.][0-9]{4,}(?:[.][0-9]+)*)\b/';
            if ($doi && $doi != '') {
                if (preg_match($doiRegex, $doi)) {
                    $this->fetchDOICitation($doi, $molecule, $currentReferenceCitations);
                    $this->fetchDOICitation($doi, $molecule, $citation_ids_array);
                } else {
                    $this->fetchCitation($doi, $molecule, $currentReferenceCitations);
                    $this->fetchCitation($doi, $molecule, $citation_ids_array);
                }
            }

            if (isset($reference['organisms']) && $reference['organisms']) {
                $this->saveOrganismDetails($reference['organisms'], $molecule, $entry, $currentReferenceCitations, $entryAuditRecords);
            }
        }

        if (! empty($citation_ids_array)) {
            // Use unique array and handle potential race conditions in citation attachment
            $uniqueCitationIds = array_unique($citation_ids_array);
            try {
                // Use database-agnostic approach for citation attachment via citables table
                foreach ($uniqueCitationIds as $citationId) {
                    // Check if relationship already exists in citables table
                    $exists = DB::selectOne(
                        'SELECT 1 FROM citables WHERE citation_id = ? AND citable_type = ? AND citable_id = ?',
                        [$citationId, 'App\\Models\\Molecule', $molecule->id]
                    );

                    if (! $exists) {
                        try {
                            DB::table('citables')->insert([
                                'citation_id' => $citationId,
                                'citable_type' => 'App\\Models\\Molecule',
                                'citable_id' => $molecule->id,
                            ]);
                        } catch (QueryException $e) {
                            // Ignore if it's a duplicate key error (race condition)
                            if (! str_contains($e->getMessage(), 'unique') && ! str_contains($e->getMessage(), 'duplicate')) {
                                throw $e;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the entire job for citation sync issues
                Log::warning("Citation sync failed for molecule {$molecule->id}: ".$e->getMessage());
            }
        }
    }

    /**
     * Fetch citation by citation text (moved from EnrichMoleculesBatch)
     */
    public function fetchCitation($citation_text, \stdClass $molecule, array &$citation_ids_array): void
    {
        // First try to find existing citation
        $existing = DB::selectOne('SELECT * FROM citations WHERE citation_text = ?', [$citation_text]);

        if ($existing) {
            $citation = $existing;
        } else {
            try {
                $citationId = DB::table('citations')->insertGetId([
                    'citation_text' => $citation_text,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $citation = DB::selectOne('SELECT * FROM citations WHERE id = ?', [$citationId]);
            } catch (QueryException $e) {
                // Handle race condition - another process might have created it
                usleep(50000); // 50ms
                $existing = DB::selectOne('SELECT * FROM citations WHERE citation_text = ?', [$citation_text]);
                if ($existing) {
                    $citation = $existing;
                } else {
                    throw $e;
                }
            }
        }

        $citation_ids_array[] = $citation->id;
    }

    /**
     * Fetch DOI citation (moved from EnrichMoleculesBatch)
     */
    public function fetchDOICitation($doi, \stdClass $molecule, array &$citation_ids_array): void
    {
        $dois = $this->extract_dois($doi);

        foreach ($dois as $doi) {
            if ($doi) {
                // Create or find citation with DOI using database-agnostic approach
                $existing = DB::selectOne('SELECT * FROM citations WHERE doi = ?', [$doi]);

                if ($existing) {
                    $citation = $existing;
                } else {
                    try {
                        $citationId = DB::table('citations')->insertGetId([
                            'doi' => $doi,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $citation = DB::selectOne('SELECT * FROM citations WHERE id = ?', [$citationId]);
                    } catch (QueryException $e) {
                        // Handle race condition - another process might have created it
                        usleep(50000); // 50ms
                        $existing = DB::selectOne('SELECT * FROM citations WHERE doi = ?', [$doi]);
                        if ($existing) {
                            $citation = $existing;
                        } else {
                            throw $e;
                        }
                    }
                }

                $citation_ids_array[] = $citation->id;
            }
        }
    }

    /**
     * Extract DOIs from a given input string (moved from EnrichMoleculesBatch)
     */
    public function extract_dois($input_string): array
    {
        $dois = [];
        $matches = [];
        // Regex pattern to match DOIs
        $pattern = '/(10\.\d{4,}(?:\.\d+)*\/\S+(?:(?!["&\'<>])\S))/i';
        // Extract DOIs using preg_match_all
        preg_match_all($pattern, $input_string, $matches);
        // Add matched DOIs to the dois array
        foreach ($matches[0] as $doi) {
            $dois[] = $doi;
        }

        // Check if the dois are split properly (especially considering that non dois are there).
        return $dois;
    }

    /**
     * Save organism details (moved from EnrichMoleculesBatch)
     */
    public function saveOrganismDetails($organisms, \stdClass $molecule, \stdClass $entry, array $citation_ids_array, array &$entryAuditRecords = []): void
    {
        // Define collection and citation IDs once
        $newCollectionIds = [$entry->collection_id];
        $newCitationIds = $citation_ids_array;

        foreach ($organisms as $organism) {
            $parts_ids = [];
            $ecosystem_ids = [];

            // Handle organism creation using database-agnostic approach
            $existing = DB::selectOne('SELECT id FROM organisms WHERE name = ?', [$organism['name']]);

            if ($existing) {
                $organismId = $existing->id;
            } else {
                try {
                    $organismId = DB::table('organisms')->insertGetId([
                        'name' => $organism['name'],
                        'slug' => Str::slug($organism['name']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    // Handle race condition - another process might have created it
                    usleep(50000); // 50ms
                    $existing = DB::selectOne('SELECT id FROM organisms WHERE name = ?', [$organism['name']]);
                    if ($existing) {
                        $organismId = $existing->id;
                    } else {
                        throw $e;
                    }
                }
            }

            // Process parts or add null if none
            if (! empty($organism['parts'])) {
                foreach ($organism['parts'] as $part) {
                    // Handle sample location creation using database-agnostic approach
                    $partName = Str::title($part);
                    $partSlug = Str::slug($part);
                    $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ?', [$partName]);

                    if ($existing) {
                        $partId = $existing->id;
                    } else {
                        try {
                            $partId = DB::table('sample_locations')->insertGetId([
                                'name' => $partName,
                                'slug' => $partSlug,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (QueryException $e) {
                            // Handle race condition - another process might have created it
                            usleep(50000); // 50ms
                            $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ?', [$partName]);
                            if ($existing) {
                                $partId = $existing->id;
                            } else {
                                throw $e;
                            }
                        }
                    }
                    $parts_ids[] = [$organismId, $partId];
                }
            } else {
                $parts_ids[] = [$organismId, null];
            }

            // Process locations or add null if none
            if (! empty($organism['locations'])) {
                foreach ($organism['locations'] as $location) {
                    $geo_location_ids = []; // Reset for each location

                    // Handle geo location creation using database-agnostic approach
                    $existing = DB::selectOne('SELECT id FROM geo_locations WHERE name = ?', [$location['name']]);

                    if ($existing) {
                        $geoLocationId = $existing->id;
                    } else {
                        try {
                            $geoLocationId = DB::table('geo_locations')->insertGetId([
                                'name' => $location['name'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } catch (QueryException $e) {
                            // Handle race condition - another process might have created it
                            usleep(50000); // 50ms
                            $existing = DB::selectOne('SELECT id FROM geo_locations WHERE name = ?', [$location['name']]);
                            if ($existing) {
                                $geoLocationId = $existing->id;
                            } else {
                                throw $e;
                            }
                        }
                    }

                    foreach ($parts_ids as $part) {
                        $geo_location_ids[] = [$part[0], $part[1], $geoLocationId];
                    }

                    // Process ecosystems or add null if none
                    if (! empty($location['ecosystems'])) {
                        foreach ($location['ecosystems'] as $ecosystemName) {
                            // Handle ecosystem creation using database-agnostic approach
                            $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ?', [$ecosystemName]);

                            if ($existing) {
                                $ecosystemId = $existing->id;
                            } else {
                                try {
                                    $ecosystemId = DB::table('ecosystems')->insertGetId([
                                        'name' => $ecosystemName,
                                        'description' => 'Ecosystem imported from entry data',
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                } catch (QueryException $e) {
                                    // Handle race condition - another process might have created it
                                    usleep(50000); // 50ms
                                    $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ?', [$ecosystemName]);
                                    if ($existing) {
                                        $ecosystemId = $existing->id;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                            foreach ($geo_location_ids as $geo_location) {
                                $ecosystem_ids[] = [$geo_location[0], $geo_location[1], $geo_location[2], $ecosystemId];
                            }
                        }
                    } else {
                        foreach ($geo_location_ids as $geo_location) {
                            $ecosystem_ids[] = [$geo_location[0], $geo_location[1], $geo_location[2], null];
                        }
                    }
                }
            } else {
                $geo_location_ids = []; // Create empty array for consistency
                foreach ($parts_ids as $part) {
                    $geo_location_ids[] = [$part[0], $part[1], null];
                }
                foreach ($geo_location_ids as $geo_location) {
                    $ecosystem_ids[] = [$geo_location[0], $geo_location[1], $geo_location[2], null];
                }
            }

            // Insert or update each relationship with collection and citation IDs using raw queries
            foreach ($ecosystem_ids as $pivot) {
                // Check if molecule-organism relationship already exists
                $existing = DB::selectOne(
                    'SELECT * FROM molecule_organism WHERE molecule_id = ? AND organism_id = ? AND sample_location_id = ? AND geo_location_id = ? AND ecosystem_id = ?',
                    [$molecule->id, $pivot[0], $pivot[1], $pivot[2], $pivot[3]]
                );

                if (! $existing) {
                    // Create new record using insertGetId and then fetch the complete record
                    try {
                        $newId = DB::table('molecule_organism')->insertGetId([
                            'molecule_id' => $molecule->id,
                            'organism_id' => $pivot[0],
                            'sample_location_id' => $pivot[1],
                            'geo_location_id' => $pivot[2],
                            'ecosystem_id' => $pivot[3],
                            'collection_ids' => json_encode($newCollectionIds),
                            'citation_ids' => json_encode($newCitationIds),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Create an audit record for the new entry
                        $this->collectAudit(
                            'molecule_organism',
                            $newId,
                            [],
                            [
                                'molecule_id' => $molecule->id,
                                'organism_id' => $pivot[0],
                                'sample_location_id' => $pivot[1],
                                'geo_location_id' => $pivot[2],
                                'ecosystem_id' => $pivot[3],
                                'collection_ids' => json_encode($newCollectionIds),
                                'citation_ids' => json_encode($newCitationIds),
                            ],
                            $entryAuditRecords,
                            'created'
                        );

                        // Successfully created, no need to update
                        continue;
                    } catch (QueryException $e) {
                        // Handle race condition - another process might have created it
                        usleep(50000); // 50ms
                        $existing = DB::selectOne(
                            'SELECT * FROM molecule_organism WHERE molecule_id = ? AND organism_id = ? AND sample_location_id = ? AND geo_location_id = ? AND ecosystem_id = ?',
                            [$molecule->id, $pivot[0], $pivot[1], $pivot[2], $pivot[3]]
                        );
                        if (! $existing) {
                            throw $e;
                        }
                        // If record was created by another process, update it below
                    }
                }

                // Record exists (either found initially or created by another process), merge the arrays
                $existingCollectionIds = json_decode($existing->collection_ids ?? '[]', true);
                $existingCitationIds = json_decode($existing->citation_ids ?? '[]', true);

                $mergedCollectionIds = array_unique(array_merge($existingCollectionIds, $newCollectionIds));
                $mergedCitationIds = array_unique(array_merge($existingCitationIds, $newCitationIds));

                // Check if there are actual changes before updating
                $existingCollectionJson = json_encode($existingCollectionIds);
                $existingCitationJson = json_encode($existingCitationIds);
                $newCollectionJson = json_encode($mergedCollectionIds);
                $newCitationJson = json_encode($mergedCitationIds);

                if ($existingCollectionJson !== $newCollectionJson || $existingCitationJson !== $newCitationJson) {
                    $oldValues = [
                        'collection_ids' => $existing->collection_ids,
                        'citation_ids' => $existing->citation_ids,
                        'updated_at' => $existing->updated_at,
                    ];

                    $newValues = [
                        'collection_ids' => $newCollectionJson,
                        'citation_ids' => $newCitationJson,
                        'updated_at' => now(),
                    ];

                    DB::table('molecule_organism')
                        ->where('id', $existing->id)
                        ->update($newValues);

                    $this->collectAudit('molecule_organism', $existing->id, $oldValues, $newValues, $entryAuditRecords);
                }
            }
        }
    }

    /**
     * Collect audit record for UPDATE operations
     */
    private function collectAudit(string $table, int $recordId, array $oldValues, array $newValues, array &$auditRecords, string $event = 'updated'): void
    {
        // Only collect audits for actual changes
        $changes = [];
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;

            // Special handling for JSON fields
            if (($key === 'collection_ids' || $key === 'citation_ids') &&
                 is_string($oldValue) && is_string($newValue)) {
                // Decode JSON strings for comparison
                $oldDecoded = json_decode($oldValue, true);
                $newDecoded = json_decode($newValue, true);

                // Sort arrays to ensure consistent comparison
                if (is_array($oldDecoded)) {
                    sort($oldDecoded);
                }
                if (is_array($newDecoded)) {
                    sort($newDecoded);
                }

                if (json_encode($oldDecoded) !== json_encode($newDecoded)) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
            // Standard comparison for non-JSON fields
            elseif ($oldValue !== $newValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        if (empty($changes)) {
            return; // No changes to audit
        }

        $auditRecords[] = [
            'auditable_type' => 'App\\Models\\'.Str::studly(Str::singular($table)),
            'auditable_id' => $recordId,
            'user_type' => 'App\\Models\\User',
            'user_id' => 11, // COCONUT curator user ID for batch processing
            'event' => $event,
            'old_values' => json_encode(array_map(fn ($change) => $change['old'], $changes)),
            'new_values' => json_encode(array_map(fn ($change) => $change['new'], $changes)),
            'url' => null,
            'ip_address' => null,
            'user_agent' => 'ImportEntriesReferencesAuto Command',
            'tags' => 'enrichment,sequential-processing',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Bulk insert all collected audit records
     */
    private function insertAuditRecords(array $auditRecords): void
    {
        if (empty($auditRecords)) {
            return;
        }

        // Count audits by type for logging
        $countsByType = [];
        foreach ($auditRecords as $record) {
            $type = $record['auditable_type'];
            $countsByType[$type] = ($countsByType[$type] ?? 0) + 1;
        }

        Log::info('Audit records by type: '.json_encode($countsByType));

        try {
            // Use chunking to avoid hitting database limits
            $chunks = array_chunk($auditRecords, 500);
            foreach ($chunks as $chunk) {
                DB::table('audits')->insert($chunk);
            }
            Log::info('Inserted '.count($auditRecords).' audit records for sequential processing');
        } catch (\Exception $e) {
            Log::error('Failed to insert audit records: '.$e->getMessage());
        }
    }
}
