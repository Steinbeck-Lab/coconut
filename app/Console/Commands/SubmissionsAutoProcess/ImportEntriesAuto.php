<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use App\Jobs\ImportEntriesBatch;
use App\Models\Collection;
use App\Models\Entry;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportEntriesAuto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:import-molecules {collection_id : The ID of the collection to import} {--trigger : Trigger subsequent commands in the processing chain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-import CheMBL processed entries for a specific collection (simple scheduling approach)';

    /**
     * Configuration variables for easy tuning
     * Can be overridden via environment variables:
     * - IMPORT_BATCH_SIZE (default: 1500)
     * - IMPORT_INDEX_THRESHOLD (default: 1000)
     */
    private $batchSize = 1500;                           // Number of entries per batch job (affects memory usage and parallelism)

    private $indexCreationThreshold = 1000;             // Minimum entries to create performance indexes (balance overhead vs benefit)

    /**
     * Initialize configuration
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $collection_id = $this->argument('collection_id');
        $triggerNext = $this->option('trigger');

        $collection = Collection::find($collection_id);

        if (! $collection) {
            Log::error("Collection with ID {$collection_id} not found.");

            return 1;
        }

        Log::info("Starting import process for collection ID: {$collection_id}");
        Log::info("Configuration: batch_size={$this->batchSize}, index_threshold={$this->indexCreationThreshold}");

        // Get all entry IDs that need processing (ordered for consistency)
        $entryIds = DB::select(
            "SELECT id FROM entries WHERE collection_id = ? AND status = 'PASSED' ORDER BY id ASC",
            [$collection_id]
        );

        $totalEntries = count($entryIds);

        if ($totalEntries == 0) {
            Log::info("No entries to process for collection {$collection_id}");

            return 0;
        }

        Log::info("Processing {$totalEntries} entries for collection {$collection_id}");

        // Convert to simple array of IDs for chunking
        $entryIds = array_column($entryIds, 'id');

        // Update collection status
        $collection->jobs_status = 'PROCESSING';
        $collection->save();

        // Create performance indexes for larger batches
        if ($totalEntries > $this->indexCreationThreshold) {
            $this->createPerformanceIndexes();
        }

        // Process entries in batches using ID chunks
        $processedEntries = 0;
        $failedEntries = [];
        $auditRecords = [];

        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalEntries);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %memory:6s%');

        // Process entries in chunks
        $entryIdChunks = array_chunk($entryIds, $this->batchSize);

        foreach ($entryIdChunks as $chunkIndex => $idChunk) {
            $offset = $chunkIndex * $this->batchSize;

            // Create placeholders for the IN clause
            $placeholders = str_repeat('?,', count($idChunk) - 1).'?';

            $entries = DB::select(
                "SELECT id, status, has_stereocenters, collection_id, link, reference_id, mol_filename, structural_comments, cm_data, molecule_id, synonyms FROM entries WHERE id IN ({$placeholders})",
                $idChunk
            );

            if (! empty($entries)) {
                Log::info('Processing batch '.($chunkIndex + 1).' of '.count($entryIdChunks).' ('.count($entries)." entries, offset: {$offset})");

                // Process each entry in the current batch
                foreach ($entries as $entry) {
                    try {
                        DB::transaction(function () use ($entry, &$auditRecords) {
                            $this->processEntryMoleculeAuto($entry, $auditRecords);
                        });
                        $processedEntries++;
                    } catch (\Throwable $e) {
                        Log::error("Failed to process entry {$entry->id}: ".$e->getMessage());
                        $failedEntries[] = [
                            'entry_id' => $entry->id,
                            'error_message' => $e->getMessage(),
                            'error_class' => get_class($e),
                        ];
                    }
                    $progressBar->advance();
                }

                // Insert audit records for this batch
                if (! empty($auditRecords)) {
                    DB::table('audits')->insert($auditRecords);
                    $auditRecords = []; // Clear after insert
                }
            }
        }

        $progressBar->finish();
        $this->newLine();

        // Log processing results
        Log::info("Processing completed for collection {$collection_id}. Processed: {$processedEntries}, Failed: ".count($failedEntries));

        if (! empty($failedEntries)) {
            Log::warning('Failed entries details:', array_slice($failedEntries, 0, 5)); // Log first 5 failures
        }

        // Drop performance indexes
        if ($totalEntries > $this->indexCreationThreshold) {
            $this->dropPerformanceIndexes();
        }

        // Update collection status
        $collection->jobs_status = 'COMPLETE';
        $collection->save();

        return 0;
    }

    /**
     * Create temporary performance indexes for import optimization
     */
    private function createPerformanceIndexes(): void
    {
        Log::info('Creating temporary performance indexes for import optimization');

        $indexes = [
            // Core molecule lookup by standard_inchi (using MD5 hash due to PostgreSQL row size limit)
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_molecules_standard_inchi ON molecules(MD5(standard_inchi))',

            // Exact molecule lookup by standard_inchi + canonical_smiles (using MD5 hashes)
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_molecules_inchi_smiles ON molecules(MD5(standard_inchi), MD5(canonical_smiles))',

            // Tautomer relationship queries (for syncAllTautomers)
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_molecule_related_lookup ON molecule_related(molecule_id, related_id, type)',

            // Collection-molecule relationship operations
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_collection_molecule_lookup ON collection_molecule(molecule_id, collection_id)',

            // Report-molecule operations (reportables table)
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_reportables_entry ON reportables(reportable_id, reportable_type) WHERE reportable_type = \'App\\Models\\Entry\'',
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
                Log::debug('Created performance index');
            } catch (\Exception $e) {
                Log::debug('Index creation skipped (may already exist): '.$e->getMessage());
            }
        }

        Log::info('Performance indexes created successfully');
    }

    /**
     * Drop performance indexes
     */
    private function dropPerformanceIndexes(): void
    {
        Log::info('Dropping temporary performance indexes after completion');

        $indexes = [
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_molecules_standard_inchi',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_molecules_inchi_smiles',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_molecule_related_lookup',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_collection_molecule_lookup',
            'DROP INDEX CONCURRENTLY IF EXISTS idx_temp_reportables_entry',
        ];

        foreach ($indexes as $sql) {
            try {
                DB::statement($sql);
                Log::debug('Dropped performance index');
            } catch (\Exception $e) {
                Log::debug('Index drop failed (may not exist): '.$e->getMessage());
            }
        }

        Log::info('Performance indexes cleaned up successfully');
    }

    /**
     * Process entry molecule with logic from ImportEntriesBatch
     */
    public function processEntryMoleculeAuto($entry, &$auditRecords): void
    {
        $molecule = null;
        $entry_synonyms = json_decode($entry->synonyms, true);
        if ($entry->has_stereocenters) {
            $data = $this->getRepresentations($entry, 'parent');
            // Pass parent molecule properties to findOrCreateMolecule
            $parent = $this->findOrCreateMolecule(
                $data['canonical_smiles'],
                $data['standard_inchi'],
                [
                    'variants_count' => 1,
                    'standard_inchi_key' => $data['standard_inchikey'],
                ],
                null,
                true,
                $auditRecords
            );

            $this->attachCollection($parent, $entry, $auditRecords);

            $data = $this->getRepresentations($entry, 'standardized');
            if ($data['has_stereo_defined']) {
                $molecule = $this->findOrCreateMolecule(
                    $data['canonical_smiles'],
                    $data['standard_inchi'],
                    [
                        'has_stereo' => true,
                        'parent_id' => $parent->id,
                        'standard_inchi_key' => $data['standard_inchikey'],
                        'has_variants' => true, // clarify: This is for all variants
                    ],
                    $parent,
                    false,
                    $auditRecords
                );
            } else {
                // Mark parent as not a palceholder
                if ($parent->is_placeholder) {
                    $this->addAuditRecord(
                        'App\\Models\\Molecule',
                        $parent->id,
                        'updated',
                        ['is_placeholder' => $parent->is_placeholder],
                        ['is_placeholder' => false],
                        $auditRecords
                    );
                    DB::update(
                        'UPDATE molecules SET is_placeholder = ?, updated_at = NOW() WHERE id = ?',
                        [false, $parent->id]
                    );
                    $parent->is_placeholder = false;
                }
                $molecule = $parent;
            }
        } else {
            $data = $this->getRepresentations($entry, 'standardized');
            $molecule = $this->findOrCreateMolecule(
                $data['canonical_smiles'],
                $data['standard_inchi'],
                [
                    'is_placeholder' => false,
                    'standard_inchi_key' => $data['standard_inchikey'],
                ],
                null,
                false,
                $auditRecords
            );
        }

        // Add new synonyms from entry to the molecule
        if ($entry_synonyms) {
            $existing_synonyms = property_exists($molecule, 'synonyms') && $molecule->synonyms !== null
                ? json_decode($molecule->synonyms, true)
                : [];
            $merged_synonyms = array_unique(array_merge($entry_synonyms, $existing_synonyms));

            // Update using raw query since $molecule is stdClass
            DB::update(
                'UPDATE molecules SET synonyms = ?, updated_at = NOW() WHERE id = ?',
                [json_encode($merged_synonyms), $molecule->id]
            );

            $this->addAuditRecord(
                'App\\Models\\Molecule',
                $molecule->id,
                'updated',
                ['synonyms' => json_encode($existing_synonyms)],
                ['synonyms' => json_encode($merged_synonyms)],
                $auditRecords
            );
        }

        // Update the entry with molecule id and set status to AUTOCURATION
        $this->addAuditRecord(
            'App\\Models\\Entry',
            $entry->id,
            'updated',
            ['molecule_id' => $entry->molecule_id, 'status' => $entry->status],
            ['molecule_id' => $molecule->id, 'status' => 'AUTOCURATION'],
            $auditRecords
        );
        DB::update('UPDATE entries SET molecule_id = ?, status = ? WHERE id = ?', [$molecule->id, 'AUTOCURATION', $entry->id]);
        $this->attachCollection($molecule, $entry, $auditRecords);

        // Attach reports to molecule
        $this->attachReportsToMolecule($entry->id, $molecule->id);
    }

    /**
     * Find or create molecule with audit tracking
     */
    public function findOrCreateMolecule($canonical_smiles, $standard_inchi, $additionalFields = [], $parentToUpdate = null, $isParent = false, &$auditRecords = [])
    {
        // Use MD5 hash for index optimization, but still use actual values for comparison
        $mol = DB::selectOne('SELECT * FROM molecules WHERE MD5(standard_inchi) = MD5(?) AND standard_inchi = ?', [$standard_inchi, $standard_inchi]);

        if ($mol) {
            if ($mol->canonical_smiles != $canonical_smiles) {
                $_mol = DB::selectOne(
                    'SELECT * FROM molecules WHERE MD5(standard_inchi) = MD5(?) AND MD5(canonical_smiles) = MD5(?) AND standard_inchi = ? AND canonical_smiles = ?',
                    [$standard_inchi, $canonical_smiles, $standard_inchi, $canonical_smiles]
                );

                if (! $_mol) {
                    $insertData = array_merge([
                        'standard_inchi' => $standard_inchi,
                        'canonical_smiles' => $canonical_smiles,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $additionalFields);

                    if ($isParent) {
                        $insertData['is_parent'] = true;
                        $insertData['is_placeholder'] = true;
                        $insertData['has_variants'] = false;
                        $insertData['variants_count'] = 0;
                    }

                    $moleculeId = DB::table('molecules')->insertGetId($insertData);
                    $_mol = (object) array_merge(['id' => $moleculeId], $insertData);

                    if ($parentToUpdate) {
                        $this->addAuditRecord(
                            'App\\Models\\Molecule',
                            $parentToUpdate->id,
                            'updated',
                            ['has_variants' => $parentToUpdate->has_variants, 'variants_count' => $parentToUpdate->variants_count],
                            ['has_variants' => true, 'variants_count' => $parentToUpdate->variants_count + 1],
                            $auditRecords
                        );

                        DB::update(
                            'UPDATE molecules SET has_variants = ?, variants_count = variants_count + 1, updated_at = NOW() WHERE id = ?',
                            [true, $parentToUpdate->id]
                        );
                    }

                    $this->syncAllTautomers($standard_inchi, $auditRecords);
                }

                $mol = $_mol;
            } else {
                // Exact match - check placeholder status
                if (isset($additionalFields['is_placeholder']) && $additionalFields['is_placeholder'] === false && $mol->is_placeholder) {
                    $this->addAuditRecord(
                        'App\\Models\\Molecule',
                        $mol->id,
                        'updated',
                        ['is_placeholder' => $mol->is_placeholder],
                        ['is_placeholder' => false],
                        $auditRecords
                    );

                    DB::update(
                        'UPDATE molecules SET is_placeholder = ?, updated_at = NOW() WHERE id = ?',
                        [false, $mol->id]
                    );

                    $mol->is_placeholder = false;
                }
            }
        } else {
            $insertData = array_merge([
                'standard_inchi' => $standard_inchi,
                'canonical_smiles' => $canonical_smiles,
                'created_at' => now(),
                'updated_at' => now(),
            ], $additionalFields);

            if ($isParent) {
                $insertData['is_parent'] = true;
                $insertData['is_placeholder'] = true;
                $insertData['has_variants'] = false;
                $insertData['variants_count'] = 0;
            }

            $moleculeId = DB::table('molecules')->insertGetId($insertData);
            $mol = (object) array_merge(['id' => $moleculeId], $insertData);
        }

        return $mol;
    }

    /**
     * Sync tautomer relationships with audit tracking
     */
    private function syncAllTautomers($standard_inchi, &$auditRecords)
    {
        $relatedMols = DB::select(
            'SELECT * FROM molecules WHERE MD5(standard_inchi) = MD5(?) AND standard_inchi = ? FOR UPDATE',
            [$standard_inchi, $standard_inchi]
        );

        if (count($relatedMols) > 1) {
            $needUpdateIds = [];
            foreach ($relatedMols as $mol) {
                if (! $mol->is_tautomer) {
                    $needUpdateIds[] = $mol->id;
                }
            }

            if (count($needUpdateIds) > 0) {
                foreach ($needUpdateIds as $moleculeId) {
                    $this->addAuditRecord(
                        'App\\Models\\Molecule',
                        $moleculeId,
                        'updated',
                        ['is_tautomer' => false],
                        ['is_tautomer' => true],
                        $auditRecords
                    );
                }

                $placeholders = str_repeat('?,', count($needUpdateIds) - 1).'?';
                DB::update(
                    "UPDATE molecules SET is_tautomer = true WHERE id IN ({$placeholders})",
                    $needUpdateIds
                );
            }

            // Create tautomer relationships
            $molIDs = array_column($relatedMols, 'id');
            $molIDsPlaceholders = str_repeat('?,', count($molIDs) - 1).'?';
            $existingRelationships = DB::select(
                "SELECT molecule_id, related_id FROM molecule_related 
                WHERE (molecule_id IN ({$molIDsPlaceholders}) OR related_id IN ({$molIDsPlaceholders})) 
                AND type = 'tautomers'",
                array_merge($molIDs, $molIDs)
            );

            $existingSet = [];
            foreach ($existingRelationships as $rel) {
                $key1 = $rel->molecule_id.'-'.$rel->related_id;
                $key2 = $rel->related_id.'-'.$rel->molecule_id;
                $existingSet[$key1] = true;
                $existingSet[$key2] = true;
            }

            $relationshipsToInsert = [];
            $currentTime = now()->format('Y-m-d H:i:s');

            foreach ($relatedMols as $mol) {
                $otherIDs = array_diff($molIDs, [$mol->id]);

                foreach ($otherIDs as $otherID) {
                    $relationshipKey = $mol->id.'-'.$otherID;

                    if (! isset($existingSet[$relationshipKey])) {
                        $relationshipsToInsert[] = [
                            'molecule_id' => $mol->id,
                            'related_id' => $otherID,
                            'type' => 'tautomers',
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime,
                        ];

                        $relationshipsToInsert[] = [
                            'molecule_id' => $otherID,
                            'related_id' => $mol->id,
                            'type' => 'tautomers',
                            'created_at' => $currentTime,
                            'updated_at' => $currentTime,
                        ];
                    }
                }
            }

            if (! empty($relationshipsToInsert)) {
                DB::table('molecule_related')->insert($relationshipsToInsert);
            }
        }
    }

    /**
     * Attach collection to molecule
     */
    public function attachCollection($molecule, $entry, &$auditRecords)
    {
        try {
            $existingRelation = DB::selectOne(
                'SELECT * FROM collection_molecule WHERE molecule_id = ? AND collection_id = ?',
                [$molecule->id, $entry->collection_id]
            );

            if ($existingRelation) {
                // Update logic with audit tracking
                $existingUrls = ! empty($existingRelation->url) ? explode('|', $existingRelation->url) : [];
                $existingReferences = ! empty($existingRelation->reference) ? explode('|', $existingRelation->reference) : [];
                $existingFilenames = ! empty($existingRelation->mol_filename) ? explode('|', $existingRelation->mol_filename) : [];
                $existingComments = ! empty($existingRelation->structural_comments) ? explode('|', $existingRelation->structural_comments) : [];

                $newUrl = $entry->link ?? '';
                $newReference = $entry->reference_id ?? '';
                $newFilename = $entry->mol_filename ?? '';
                $newComment = $entry->structural_comments ?? '';

                $combinationExists = false;
                for ($i = 0; $i < count($existingReferences); $i++) {
                    if (
                        isset($existingReferences[$i]) && $existingReferences[$i] === $newReference &&
                        isset($existingUrls[$i]) && $existingUrls[$i] === $newUrl &&
                        isset($existingFilenames[$i]) && $existingFilenames[$i] === $newFilename &&
                        isset($existingComments[$i]) && $existingComments[$i] === $newComment
                    ) {
                        $combinationExists = true;
                        break;
                    }
                }

                if (! $combinationExists && ! empty($newReference)) {
                    $existingUrls[] = $newUrl;
                    $existingReferences[] = $newReference;
                    $existingFilenames[] = $newFilename;
                    $existingComments[] = $newComment;
                }

                $finalUrls = implode('|', array_filter($existingUrls, function ($url) {
                    return $url !== '';
                }));
                $finalReferences = implode('|', array_filter($existingReferences, function ($ref) {
                    return $ref !== '';
                }));
                $finalFilenames = implode('|', array_filter($existingFilenames, function ($file) {
                    return $file !== '';
                }));
                $finalComments = implode('|', array_filter($existingComments, function ($comment) {
                    return $comment !== '';
                }));

                $this->addAuditRecord(
                    'App\\Models\\CollectionMolecule',
                    $existingRelation->id,
                    'updated',
                    [
                        'url' => $existingRelation->url,
                        'reference' => $existingRelation->reference,
                        'mol_filename' => $existingRelation->mol_filename,
                        'structural_comments' => $existingRelation->structural_comments,
                    ],
                    [
                        'url' => $finalUrls,
                        'reference' => $finalReferences,
                        'mol_filename' => $finalFilenames,
                        'structural_comments' => $finalComments,
                    ],
                    $auditRecords
                );

                DB::update(
                    'UPDATE collection_molecule SET 
                        url = ?, 
                        reference = ?, 
                        mol_filename = ?, 
                        structural_comments = ?,
                        updated_at = NOW()
                    WHERE molecule_id = ? AND collection_id = ?',
                    [
                        $finalUrls,
                        $finalReferences,
                        $finalFilenames,
                        $finalComments,
                        $molecule->id,
                        $entry->collection_id,
                    ]
                );
            } else {
                DB::insert(
                    'INSERT INTO collection_molecule (molecule_id, collection_id, url, reference, mol_filename, structural_comments, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [
                        $molecule->id,
                        $entry->collection_id,
                        $entry->link,
                        $entry->reference_id,
                        $entry->mol_filename,
                        $entry->structural_comments,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning("Error attaching collection to molecule {$molecule->id} for entry {$entry->id}: ".$e->getMessage());
        }
    }

    /**
     * Attach reports to molecule
     */
    private function attachReportsToMolecule($entryId, $moleculeId)
    {
        $reportId = DB::selectOne(
            'SELECT report_id FROM reportables 
             WHERE reportable_id = ? AND reportable_type = ?',
            [$entryId, 'App\\Models\\Entry']
        )?->report_id;

        if ($reportId) {
            DB::insert(
                'INSERT IGNORE INTO reportables (report_id, reportable_type, reportable_id) VALUES (?, ?, ?)',
                [$reportId, 'App\\Models\\Molecule', $moleculeId]
            );
        }
    }

    /**
     * Add audit record
     */
    private function addAuditRecord($auditableType, $auditableId, $event, $oldValues, $newValues, &$auditRecords)
    {
        $auditRecords[] = [
            'user_type' => 'App\\Models\\User',
            'user_id' => 11,
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($newValues),
            'url' => null,
            'ip_address' => null,
            'user_agent' => null,
            'tags' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Get molecular representations
     */
    private function getRepresentations($entry, $type)
    {
        $cmData = json_decode($entry->cm_data, true);

        if ($type === 'parent') {
            return [
                'canonical_smiles' => $cmData['parent']['representations']['canonical_smiles'] ?? '',
                'standard_inchi' => $cmData['parent']['representations']['standard_inchi'] ?? '',
                'standard_inchikey' => $cmData['parent']['representations']['standard_inchikey'] ?? '',
            ];
        } else {
            return [
                'canonical_smiles' => $cmData['standardized']['representations']['canonical_smiles'] ?? '',
                'standard_inchi' => $cmData['standardized']['representations']['standard_inchi'] ?? '',
                'standard_inchikey' => $cmData['standardized']['representations']['standard_inchikey'] ?? '',
                'has_stereo_defined' => $cmData['standardized']['has_stereo_defined'] ?? false,
            ];
        }
    }
}
