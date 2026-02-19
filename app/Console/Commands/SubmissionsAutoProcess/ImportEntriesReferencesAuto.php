<?php

namespace App\Console\Commands\SubmissionsAutoProcess;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportEntriesReferencesAuto extends Command
{
    protected $signature = 'coconut:enrich-molecules {collection_id : The ID of the collection to import references for}';

    protected $description = 'Import references and organism details for entries in AUTOCURATION status';

    private int $batchSize = 1500;

    private int $indexCreationThreshold = 1000;

    private array $citationCache = [];

    private array $organismCache = [];

    private array $sampleLocationCache = [];

    private array $geoLocationCache = [];

    private array $ecosystemCache = [];

    private array $auditRecords = [];

    public function handle(): int
    {
        $collectionId = $this->argument('collection_id');

        $collection = DB::selectOne('SELECT * FROM collections WHERE id = ?', [$collectionId]);
        if (! $collection) {
            $this->error("Collection with ID {$collectionId} not found.");
            Log::error("Collection with ID {$collectionId} not found.");

            return self::FAILURE;
        }

        $this->info("Importing references for collection ID: {$collectionId}");
        Log::info("Importing references for collection ID: {$collectionId}");

        // Update collection status
        DB::update(
            'UPDATE collections SET jobs_status = ?, job_info = ?, updated_at = ? WHERE id = ?',
            ['PROCESSING', 'Importing references: Citations and Organism Info', now(), $collectionId]
        );

        // Build query for entries in AUTOCURATION status
        $query = DB::table('entries')
            ->where('status', '=', 'AUTOCURATION')
            ->whereNotNull('molecule_id')
            ->whereNotNull('meta_data')
            ->where('collection_id', '=', $collectionId);

        $totalCount = $query->count();
        Log::info("Found {$totalCount} entries to process for enrichment in collection ID: {$collectionId}");

        if ($totalCount === 0) {
            $this->info('No entries found to process.');
            Log::info("No entries found in AUTOCURATION status for collection ID {$collectionId}.");
            DB::update(
                'UPDATE collections SET jobs_status = ?, job_info = ?, updated_at = ? WHERE id = ?',
                ['COMPLETE', '', now(), $collectionId]
            );

            return self::SUCCESS;
        }

        // Create performance indexes for larger batches
        if ($totalCount > $this->indexCreationThreshold) {
            $this->createPerformanceIndexes();
        }

        $this->info("Processing {$totalCount} entries...");
        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat(' %current%/%max% [%bar%] %percent%% %elapsed%/%estimated% %memory%');
        $bar->start();

        $successCount = 0;
        $failedCount = 0;
        $startTime = microtime(true);

        $query->orderBy('id')->chunkById($this->batchSize, function ($entries) use ($bar, &$successCount, &$failedCount) {
            foreach ($entries as $entry) {
                try {
                    DB::transaction(function () use ($entry) {
                        $this->processEntry($entry);
                    });
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to process entry {$entry->id}: ".$e->getMessage());
                }
                $bar->advance();
            }

            // Flush audit records after each chunk to avoid memory exhaustion
            if (! empty($this->auditRecords)) {
                $this->insertAuditRecords();
            }
        });

        $bar->finish();
        $this->newLine();

        $duration = round(microtime(true) - $startTime, 2);

        $this->info('Enrichment completed successfully!');
        $this->line("Total entries processed: {$totalCount}");
        $this->line("Successful: {$successCount}");
        $this->line("Failed: {$failedCount}");
        $this->line("Total duration: {$duration}s");
        Log::info("All entries completed: {$successCount} successful, {$failedCount} failed out of {$totalCount} entries (Duration: {$duration}s)");

        // Flush remaining audit records
        if (! empty($this->auditRecords)) {
            $this->insertAuditRecords();
        }

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
            ['COMPLETE', '', now(), $collectionId]
        );

        Log::info("References import process completed for collection ID {$collectionId}.");

        return self::SUCCESS;
    }

    /**
     * Create temporary performance indexes for enrichment optimization.
     */
    private function createPerformanceIndexes(): void
    {
        Log::info('Creating temporary performance indexes for enrichment optimization');

        $indexes = [
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_temp_citations_text ON citations(citation_text)',
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
     * Drop temporary performance indexes.
     */
    private function dropPerformanceIndexes(): void
    {
        Log::info('Dropping temporary enrichment performance indexes');

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

    // ===========================
    // Core Processing Methods
    // ===========================

    /**
     * Process a single entry and update molecule_organism records.
     */
    protected function processEntry(object $entry): void
    {
        $metaData = json_decode($entry->meta_data, true);
        if (! $metaData || ! isset($metaData['m'])) {
            Log::warning("Entry {$entry->id} has no valid meta_data. Skipping.");

            return;
        }

        $nmd = $metaData['m'];
        $mappingStatus = $nmd['ms'] ?? 'ambiguous';
        $references = $nmd['refs'] ?? [];

        // Get flat arrays for aggregation
        $flatDois = $nmd['dois'] ?? [];
        $flatOrganisms = $nmd['orgs'] ?? [];
        $flatParts = $nmd['prts'] ?? [];
        $flatGeoLocations = $nmd['geos'] ?? [];
        $flatEcosystems = $nmd['ecos'] ?? [];

        // Extract unique organisms
        $organismsToProcess = $this->extractOrganismsFromReferences($references, $flatOrganisms);

        if (empty($organismsToProcess)) {
            Log::warning("No organisms found for entry {$entry->id}. Skipping.");

            return;
        }

        // Pre-resolve flat IDs once for reuse
        $resolvedCitIds = $this->resolveCitationIds($flatDois);
        $resolvedGeoIds = $this->resolveGeoLocationIds($flatGeoLocations);
        $resolvedEcoIds = $this->resolveEcosystemIds($flatEcosystems);

        foreach ($organismsToProcess as $organismName) {
            if (empty($organismName)) {
                continue;
            }

            $organismId = $this->findOrCreateOrganism($organismName);
            if (! $organismId) {
                Log::warning("Could not find or create organism '{$organismName}' for entry {$entry->id}. Skipping.");

                continue;
            }

            $resolvedSmpIds = $this->resolveSampleLocationIds($flatParts, $organismId);

            // Build the metadata JSON structure for this molecule-organism pair
            $metadata = $this->buildOrganismMetadata(
                $entry,
                $mappingStatus,
                $references,
                $organismName,
                $organismId,
                $resolvedCitIds,
                $resolvedSmpIds,
                $resolvedGeoIds,
                $resolvedEcoIds
            );

            // Upsert molecule_organism record
            $this->upsertMoleculeOrganism(
                $entry->molecule_id,
                $organismId,
                $entry->collection_id,
                $metadata
            );

            // Link organism to its specific geo_locations from references
            $organismGeoLocations = $this->extractOrganismGeoLocations($references, $organismName);
            if (! empty($organismGeoLocations)) {
                $this->processGeoLocationOrganism($organismId, $organismGeoLocations);
            } elseif (! empty($flatGeoLocations)) {
                $this->processGeoLocationOrganism($organismId, $flatGeoLocations);
            }
        }

        // Process geo_location_molecule pivot
        if (! empty($flatGeoLocations)) {
            $this->processGeoLocationMolecule($entry->molecule_id, $flatGeoLocations, $flatEcosystems);
        }

        // Process citables (molecule-citation and collection-citation links)
        if (! empty($flatDois)) {
            $this->processCitables($entry->molecule_id, $entry->collection_id, $flatDois);
        }

        // Mark curation status as completed
        updateCurationStatus($entry->molecule_id, 'enrich-molecules', 'completed');

        // Set entry status to IMPORTED
        DB::table('entries')->where('id', $entry->id)->update(['status' => 'IMPORTED']);
    }

    /**
     * Extract unique organism names from references and flat arrays.
     */
    protected function extractOrganismsFromReferences(array $references, array $flatOrganisms): array
    {
        $organisms = [];

        foreach ($references as $ref) {
            if (isset($ref['orgs']) && is_array($ref['orgs'])) {
                foreach ($ref['orgs'] as $org) {
                    if (isset($org['nm']) && ! empty($org['nm'])) {
                        $organisms[] = $org['nm'];
                    }
                }
            }
        }

        foreach ($flatOrganisms as $org) {
            if (! empty($org)) {
                $organisms[] = $org;
            }
        }

        return array_unique($organisms);
    }

    /**
     * Build the metadata JSON structure for a molecule-organism record.
     */
    protected function buildOrganismMetadata(
        object $entry,
        string $mappingStatus,
        array $references,
        string $organismName,
        int $organismId,
        array $resolvedCitIds,
        array $resolvedSmpIds,
        array $resolvedGeoIds,
        array $resolvedEcoIds
    ): array {
        $collectionEntry = [
            'id' => $entry->collection_id,
            'map' => $mappingStatus,
            'refs' => [],
        ];

        if ($mappingStatus === 'ambiguous') {
            $collectionEntry['unres'] = [
                'cit_ids' => $resolvedCitIds,
                'smp_ids' => $resolvedSmpIds,
                'geo_ids' => $resolvedGeoIds,
                'eco_ids' => $resolvedEcoIds,
            ];

            $metadata = [
                'cols' => [$collectionEntry],
                'cit_ids' => $resolvedCitIds,
                'col_ids' => [$entry->collection_id],
                'smp_ids' => $resolvedSmpIds,
                'geo_ids' => $resolvedGeoIds,
                'eco_ids' => $resolvedEcoIds,
            ];
        } else {
            $structuredRefs = $this->buildStructuredReferences($references, $organismName, $organismId);
            $collectionEntry['refs'] = $structuredRefs;

            // Derive root-level IDs from organism-specific structured refs
            $orgCitIds = [];
            $orgSmpIds = [];
            $orgGeoIds = [];
            $orgEcoIds = [];
            foreach ($structuredRefs as $ref) {
                if (! empty($ref['cit_id'])) {
                    $orgCitIds[] = $ref['cit_id'];
                }
                $orgSmpIds = array_merge($orgSmpIds, $ref['smp_ids'] ?? []);
                foreach (($ref['locs'] ?? []) as $loc) {
                    if (! empty($loc['geo_id'])) {
                        $orgGeoIds[] = $loc['geo_id'];
                    }
                    $orgEcoIds = array_merge($orgEcoIds, $loc['eco_ids'] ?? []);
                }
            }

            $metadata = [
                'cols' => [$collectionEntry],
                'cit_ids' => array_values(array_unique($orgCitIds)),
                'col_ids' => [$entry->collection_id],
                'smp_ids' => array_values(array_unique($orgSmpIds)),
                'geo_ids' => array_values(array_unique($orgGeoIds)),
                'eco_ids' => array_values(array_unique($orgEcoIds)),
            ];
        }

        return $metadata;
    }

    /**
     * Extract geo-location names specific to an organism from structured references.
     */
    protected function extractOrganismGeoLocations(array $references, string $organismName): array
    {
        $geoLocations = [];

        foreach ($references as $ref) {
            foreach (($ref['orgs'] ?? []) as $org) {
                if (($org['nm'] ?? '') === $organismName) {
                    foreach (($org['locs'] ?? []) as $loc) {
                        if (! empty($loc['nm'])) {
                            $geoLocations[] = $loc['nm'];
                        }
                    }
                }
            }
        }

        return array_values(array_unique($geoLocations));
    }

    /**
     * Build structured references array for full/inferred mapping.
     */
    protected function buildStructuredReferences(array $references, string $organismName, int $organismId): array
    {
        $structuredRefs = [];

        foreach ($references as $ref) {
            $doi = $ref['doi'] ?? '';
            $organisms = $ref['orgs'] ?? [];

            // Find organism data matching our organism name
            $matchingOrganism = null;
            foreach ($organisms as $org) {
                if (isset($org['nm']) && $org['nm'] === $organismName) {
                    $matchingOrganism = $org;
                    break;
                }
            }

            if (! $matchingOrganism) {
                continue;
            }

            $citationId = $this->findOrCreateCitation($doi);

            $refEntry = [
                'cit_id' => $citationId,
                'smp_ids' => [],
                'locs' => [],
            ];

            // Get sample location IDs from parts
            $parts = $matchingOrganism['prts'] ?? [];
            $refEntry['smp_ids'] = $this->resolveSampleLocationIds($parts, $organismId);

            // Get locations with geo_location and ecosystems
            $locations = $matchingOrganism['locs'] ?? [];
            foreach ($locations as $loc) {
                $geoLocationId = null;
                if (! empty($loc['nm'])) {
                    $geoLocationId = $this->findOrCreateGeoLocation($loc['nm']);
                }

                $ecosystems = $loc['ecos'] ?? [];
                $ecosystemIds = $this->resolveEcosystemIds($ecosystems, $geoLocationId);

                $refEntry['locs'][] = [
                    'geo_id' => $geoLocationId,
                    'eco_ids' => $ecosystemIds,
                ];
            }

            $structuredRefs[] = $refEntry;
        }

        return $structuredRefs;
    }

    // ===========================
    // Upsert & Pivot Methods
    // ===========================

    /**
     * Upsert molecule_organism record with metadata.
     */
    protected function upsertMoleculeOrganism(
        int $moleculeId,
        int $organismId,
        int $collectionId,
        array $newMetadata
    ): void {
        $existing = DB::selectOne(
            'SELECT * FROM molecule_organism WHERE molecule_id = ? AND organism_id = ? LIMIT 1',
            [$moleculeId, $organismId]
        );

        if ($existing) {
            $existingMetadata = json_decode($existing->metadata ?? '{}', true);
            $mergedMetadata = $this->mergeMetadata($existingMetadata, $newMetadata);

            $existingCollectionIds = json_decode($existing->collection_ids ?? '[]', true);
            $existingCitationIds = json_decode($existing->citation_ids ?? '[]', true);

            $mergedCollectionIds = array_values(array_unique(array_merge($existingCollectionIds, [$collectionId])));
            $mergedCitationIds = array_values(array_unique(array_merge($existingCitationIds, $newMetadata['cit_ids'] ?? [])));

            $oldValues = [
                'metadata' => $existing->metadata,
                'collection_ids' => $existing->collection_ids,
                'citation_ids' => $existing->citation_ids,
            ];

            $newValues = [
                'metadata' => json_encode($mergedMetadata),
                'collection_ids' => json_encode($mergedCollectionIds),
                'citation_ids' => json_encode($mergedCitationIds),
                'updated_at' => now(),
            ];

            DB::table('molecule_organism')
                ->where('id', $existing->id)
                ->update($newValues);

            $this->collectAudit('molecule_organism', $existing->id, $oldValues, $newValues);
        } else {
            try {
                $newId = DB::table('molecule_organism')->insertGetId([
                    'molecule_id' => $moleculeId,
                    'organism_id' => $organismId,
                    'sample_location_id' => null,
                    'geo_location_id' => null,
                    'ecosystem_id' => null,
                    'collection_ids' => json_encode([$collectionId]),
                    'citation_ids' => json_encode($newMetadata['cit_ids'] ?? []),
                    'metadata' => json_encode($newMetadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->collectAudit('molecule_organism', $newId, [], [
                    'molecule_id' => $moleculeId,
                    'organism_id' => $organismId,
                    'collection_ids' => json_encode([$collectionId]),
                    'citation_ids' => json_encode($newMetadata['cit_ids'] ?? []),
                    'metadata' => json_encode($newMetadata),
                ], 'created');
            } catch (QueryException $e) {
                usleep(50000);
                $existing = DB::selectOne(
                    'SELECT * FROM molecule_organism WHERE molecule_id = ? AND organism_id = ? LIMIT 1',
                    [$moleculeId, $organismId]
                );
                if ($existing) {
                    $this->upsertMoleculeOrganism($moleculeId, $organismId, $collectionId, $newMetadata);
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * Process and insert geo_location_molecule pivot records.
     */
    protected function processGeoLocationMolecule(
        int $moleculeId,
        array $flatGeoLocations,
        array $flatEcosystems
    ): void {
        foreach ($flatGeoLocations as $geoLocationName) {
            if (empty($geoLocationName)) {
                continue;
            }

            $geoLocationId = $this->findOrCreateGeoLocation($geoLocationName);
            if (! $geoLocationId) {
                continue;
            }

            $locationsText = ! empty($flatEcosystems) ? implode(', ', array_filter($flatEcosystems)) : null;

            $existing = DB::selectOne(
                'SELECT * FROM geo_location_molecule WHERE molecule_id = ? AND geo_location_id = ?',
                [$moleculeId, $geoLocationId]
            );

            if ($existing) {
                if ($existing->locations !== $locationsText) {
                    DB::table('geo_location_molecule')
                        ->where('id', $existing->id)
                        ->update([
                            'locations' => $locationsText,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                try {
                    DB::table('geo_location_molecule')->insert([
                        'molecule_id' => $moleculeId,
                        'geo_location_id' => $geoLocationId,
                        'locations' => $locationsText,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    usleep(50000);
                    $existing = DB::selectOne(
                        'SELECT * FROM geo_location_molecule WHERE molecule_id = ? AND geo_location_id = ?',
                        [$moleculeId, $geoLocationId]
                    );
                    if (! $existing) {
                        throw $e;
                    }
                }
            }
        }
    }

    /**
     * Process and insert geo_location_organism pivot records.
     */
    protected function processGeoLocationOrganism(
        int $organismId,
        array $flatGeoLocations
    ): void {
        foreach ($flatGeoLocations as $geoLocationName) {
            if (empty($geoLocationName)) {
                continue;
            }

            $geoLocationId = $this->findOrCreateGeoLocation($geoLocationName);
            if (! $geoLocationId) {
                continue;
            }

            $existing = DB::selectOne(
                'SELECT * FROM geo_location_organism WHERE organism_id = ? AND geo_location_id = ?',
                [$organismId, $geoLocationId]
            );

            if (! $existing) {
                try {
                    DB::table('geo_location_organism')->insert([
                        'organism_id' => $organismId,
                        'geo_location_id' => $geoLocationId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    usleep(50000);
                    $existing = DB::selectOne(
                        'SELECT * FROM geo_location_organism WHERE organism_id = ? AND geo_location_id = ?',
                        [$organismId, $geoLocationId]
                    );
                    if (! $existing) {
                        Log::warning("Failed to insert geo_location_organism for organism={$organismId}, geo_location={$geoLocationId}: ".$e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Process and insert citables records (molecule-citation and collection-citation links).
     */
    protected function processCitables(
        int $moleculeId,
        int $collectionId,
        array $flatDois
    ): void {
        foreach ($flatDois as $doi) {
            if (empty($doi)) {
                continue;
            }

            $citationId = $this->findOrCreateCitation($doi);
            if (! $citationId) {
                continue;
            }

            // Link citation to molecule
            $existingMolecule = DB::selectOne(
                'SELECT * FROM citables WHERE citation_id = ? AND citable_id = ? AND citable_type = ?',
                [$citationId, $moleculeId, 'App\\Models\\Molecule']
            );

            if (! $existingMolecule) {
                try {
                    DB::table('citables')->insert([
                        'citation_id' => $citationId,
                        'citable_id' => $moleculeId,
                        'citable_type' => 'App\\Models\\Molecule',
                    ]);
                } catch (QueryException $e) {
                    usleep(50000);
                    $existingMolecule = DB::selectOne(
                        'SELECT * FROM citables WHERE citation_id = ? AND citable_id = ? AND citable_type = ?',
                        [$citationId, $moleculeId, 'App\\Models\\Molecule']
                    );
                    if (! $existingMolecule) {
                        Log::warning("Failed to insert citables for molecule={$moleculeId}, citation={$citationId}: ".$e->getMessage());
                    }
                }
            }

            // Link citation to collection
            $existingCollection = DB::selectOne(
                'SELECT * FROM citables WHERE citation_id = ? AND citable_id = ? AND citable_type = ?',
                [$citationId, $collectionId, 'App\\Models\\Collection']
            );

            if (! $existingCollection) {
                try {
                    DB::table('citables')->insert([
                        'citation_id' => $citationId,
                        'citable_id' => $collectionId,
                        'citable_type' => 'App\\Models\\Collection',
                    ]);
                } catch (QueryException $e) {
                    usleep(50000);
                    $existingCollection = DB::selectOne(
                        'SELECT * FROM citables WHERE citation_id = ? AND citable_id = ? AND citable_type = ?',
                        [$citationId, $collectionId, 'App\\Models\\Collection']
                    );
                    if (! $existingCollection) {
                        Log::warning("Failed to insert citables for collection={$collectionId}, citation={$citationId}: ".$e->getMessage());
                    }
                }
            }
        }
    }

    // ===========================
    // Metadata Merge
    // ===========================

    /**
     * Merge two metadata structures, combining collections arrays.
     */
    protected function mergeMetadata(array $existing, array $new): array
    {
        $collectionsById = [];
        foreach (($existing['cols'] ?? []) as $col) {
            $collectionsById[$col['id']] = $col;
        }
        foreach (($new['cols'] ?? []) as $col) {
            if (isset($collectionsById[$col['id']])) {
                // Merge references for same collection, deduplicating by cit_id
                $existingRefs = $collectionsById[$col['id']]['refs'] ?? [];
                $refsByCitId = [];
                foreach ($existingRefs as $ref) {
                    $key = $ref['cit_id'] ?? 'null';
                    $refsByCitId[$key] = $ref;
                }
                foreach (($col['refs'] ?? []) as $ref) {
                    $key = $ref['cit_id'] ?? 'null';
                    if (isset($refsByCitId[$key])) {
                        $refsByCitId[$key]['smp_ids'] = array_values(array_unique(array_merge(
                            $refsByCitId[$key]['smp_ids'] ?? [],
                            $ref['smp_ids'] ?? []
                        )));
                        $existingLocs = $refsByCitId[$key]['locs'] ?? [];
                        $existingGeoIds = array_column($existingLocs, 'geo_id');
                        foreach (($ref['locs'] ?? []) as $loc) {
                            if (! in_array($loc['geo_id'], $existingGeoIds)) {
                                $refsByCitId[$key]['locs'][] = $loc;
                            }
                        }
                    } else {
                        $refsByCitId[$key] = $ref;
                    }
                }
                $collectionsById[$col['id']]['refs'] = array_values($refsByCitId);

                // Merge unresolved if present
                if (isset($col['unres'])) {
                    $existingUnresolved = $collectionsById[$col['id']]['unres'] ?? [];
                    $collectionsById[$col['id']]['unres'] = [
                        'cit_ids' => array_values(array_unique(array_merge(
                            $existingUnresolved['cit_ids'] ?? [],
                            $col['unres']['cit_ids'] ?? []
                        ))),
                        'smp_ids' => array_values(array_unique(array_merge(
                            $existingUnresolved['smp_ids'] ?? [],
                            $col['unres']['smp_ids'] ?? []
                        ))),
                        'geo_ids' => array_values(array_unique(array_merge(
                            $existingUnresolved['geo_ids'] ?? [],
                            $col['unres']['geo_ids'] ?? []
                        ))),
                        'eco_ids' => array_values(array_unique(array_merge(
                            $existingUnresolved['eco_ids'] ?? [],
                            $col['unres']['eco_ids'] ?? []
                        ))),
                    ];
                }
            } else {
                $collectionsById[$col['id']] = $col;
            }
        }

        return [
            'cols' => array_values($collectionsById),
            'cit_ids' => array_values(array_unique(array_merge(
                $existing['cit_ids'] ?? [],
                $new['cit_ids'] ?? []
            ))),
            'col_ids' => array_values(array_unique(array_merge(
                $existing['col_ids'] ?? [],
                $new['col_ids'] ?? []
            ))),
            'smp_ids' => array_values(array_unique(array_merge(
                $existing['smp_ids'] ?? [],
                $new['smp_ids'] ?? []
            ))),
            'geo_ids' => array_values(array_unique(array_merge(
                $existing['geo_ids'] ?? [],
                $new['geo_ids'] ?? []
            ))),
            'eco_ids' => array_values(array_unique(array_merge(
                $existing['eco_ids'] ?? [],
                $new['eco_ids'] ?? []
            ))),
        ];
    }

    // ===========================
    // Resolution Methods
    // ===========================

    protected function resolveCitationIds(array $dois): array
    {
        $ids = [];
        foreach ($dois as $doi) {
            if (! empty($doi)) {
                $id = $this->findOrCreateCitation($doi);
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveSampleLocationIds(array $parts, ?int $organismId = null): array
    {
        $ids = [];
        foreach ($parts as $part) {
            if (! empty($part)) {
                $id = $this->findOrCreateSampleLocation($part, $organismId);
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveGeoLocationIds(array $locations): array
    {
        $ids = [];
        foreach ($locations as $location) {
            if (! empty($location)) {
                $id = $this->findOrCreateGeoLocation($location);
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveEcosystemIds(array $ecosystems, ?int $geoLocationId = null): array
    {
        $ids = [];
        foreach ($ecosystems as $ecosystem) {
            if (! empty($ecosystem)) {
                $id = $this->findOrCreateEcosystem($ecosystem, $geoLocationId);
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    // ===========================
    // Find or Create Methods
    // ===========================

    /**
     * Find or create a citation. Supports both DOI and plain citation text.
     */
    protected function findOrCreateCitation(string $doiOrText): ?int
    {
        if (empty($doiOrText)) {
            return null;
        }

        // Try to extract DOI
        $doi = $this->extractDoi($doiOrText);

        if (! empty($doi)) {
            return $this->findOrCreateCitationByDoi($doi);
        }

        // Non-DOI: treat as citation text
        return $this->findOrCreateCitationByText($doiOrText);
    }

    /**
     * Find or create a citation by DOI.
     */
    protected function findOrCreateCitationByDoi(string $doi): ?int
    {
        $cacheKey = "doi:{$doi}";

        if (isset($this->citationCache[$cacheKey])) {
            return $this->citationCache[$cacheKey];
        }

        $existing = DB::selectOne('SELECT id FROM citations WHERE doi = ?', [$doi]);
        if ($existing) {
            $this->citationCache[$cacheKey] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('citations')->insertGetId([
                'doi' => $doi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->citationCache[$cacheKey] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM citations WHERE doi = ?', [$doi]);
            if ($existing) {
                $this->citationCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    /**
     * Find or create a citation by plain text (non-DOI).
     */
    protected function findOrCreateCitationByText(string $citationText): ?int
    {
        $cacheKey = 'text:'.md5($citationText);

        if (isset($this->citationCache[$cacheKey])) {
            return $this->citationCache[$cacheKey];
        }

        $existing = DB::selectOne('SELECT id FROM citations WHERE citation_text = ?', [$citationText]);
        if ($existing) {
            $this->citationCache[$cacheKey] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('citations')->insertGetId([
                'citation_text' => $citationText,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->citationCache[$cacheKey] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM citations WHERE citation_text = ?', [$citationText]);
            if ($existing) {
                $this->citationCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    protected function findOrCreateOrganism(string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        if (isset($this->organismCache[$name])) {
            return $this->organismCache[$name];
        }

        $existing = DB::selectOne('SELECT id FROM organisms WHERE name = ?', [$name]);
        if ($existing) {
            $this->organismCache[$name] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('organisms')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->organismCache[$name] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM organisms WHERE name = ?', [$name]);
            if ($existing) {
                $this->organismCache[$name] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    /**
     * Find or create a sample location. If an existing record matches by name
     * but has no organism_id, the foreign key is adopted onto the existing record.
     */
    protected function findOrCreateSampleLocation(string $name, ?int $organismId = null): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = Str::title($name);
        $cacheKey = $organismId ? "{$name}_{$organismId}" : $name;

        if (isset($this->sampleLocationCache[$cacheKey])) {
            return $this->sampleLocationCache[$cacheKey];
        }

        if ($organismId) {
            // First check for exact match (name + organism_id)
            $existing = DB::selectOne(
                'SELECT id FROM sample_locations WHERE name = ? AND organism_id = ?',
                [$name, $organismId]
            );
            if ($existing) {
                $this->sampleLocationCache[$cacheKey] = $existing->id;

                return $existing->id;
            }

            // Check for existing record with same name but no organism_id — adopt it
            $existingNoOrg = DB::selectOne(
                'SELECT id FROM sample_locations WHERE name = ? AND organism_id IS NULL',
                [$name]
            );
            if ($existingNoOrg) {
                DB::table('sample_locations')
                    ->where('id', $existingNoOrg->id)
                    ->update(['organism_id' => $organismId, 'updated_at' => now()]);
                $this->sampleLocationCache[$cacheKey] = $existingNoOrg->id;

                return $existingNoOrg->id;
            }
        } else {
            $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ?', [$name]);
            if ($existing) {
                $this->sampleLocationCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
        }

        try {
            $id = DB::table('sample_locations')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'organism_id' => $organismId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->sampleLocationCache[$cacheKey] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            if ($organismId) {
                $existing = DB::selectOne(
                    'SELECT id FROM sample_locations WHERE name = ? AND organism_id = ?',
                    [$name, $organismId]
                );
            } else {
                $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ?', [$name]);
            }
            if ($existing) {
                $this->sampleLocationCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    protected function findOrCreateGeoLocation(string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        if (isset($this->geoLocationCache[$name])) {
            return $this->geoLocationCache[$name];
        }

        $existing = DB::selectOne('SELECT id FROM geo_locations WHERE name = ?', [$name]);
        if ($existing) {
            $this->geoLocationCache[$name] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('geo_locations')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->geoLocationCache[$name] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM geo_locations WHERE name = ?', [$name]);
            if ($existing) {
                $this->geoLocationCache[$name] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    protected function findOrCreateEcosystem(string $name, ?int $geoLocationId = null): ?int
    {
        if (empty($name)) {
            return null;
        }

        $cacheKey = $geoLocationId ? "{$name}_{$geoLocationId}" : $name;

        if (isset($this->ecosystemCache[$cacheKey])) {
            return $this->ecosystemCache[$cacheKey];
        }

        if ($geoLocationId) {
            $existing = DB::selectOne(
                'SELECT id FROM ecosystems WHERE name = ? AND geo_location_id = ?',
                [$name, $geoLocationId]
            );
            if ($existing) {
                $this->ecosystemCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
        } else {
            $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ?', [$name]);
            if ($existing) {
                $this->ecosystemCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
        }

        try {
            $id = DB::table('ecosystems')->insertGetId([
                'name' => $name,
                'description' => 'Ecosystem imported from entry data',
                'geo_location_id' => $geoLocationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->ecosystemCache[$cacheKey] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            if ($geoLocationId) {
                $existing = DB::selectOne(
                    'SELECT id FROM ecosystems WHERE name = ? AND geo_location_id = ?',
                    [$name, $geoLocationId]
                );
            } else {
                $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ?', [$name]);
            }
            if ($existing) {
                $this->ecosystemCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    // ===========================
    // Helper Methods
    // ===========================

    /**
     * Extract DOI from a string (handles URLs and extra text).
     */
    protected function extractDoi(string $input): string
    {
        $pattern = '/(10\.\d{4,}(?:\.\d+)*\/\S+(?:(?!["&\'<>])\S))/i';
        if (preg_match($pattern, $input, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Collect audit record for tracking changes.
     */
    protected function collectAudit(string $table, int $recordId, array $oldValues, array $newValues, string $event = 'updated'): void
    {
        $changes = [];
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        if (empty($changes)) {
            return;
        }

        $this->auditRecords[] = [
            'auditable_type' => 'App\\Models\\'.Str::studly(Str::singular($table)),
            'auditable_id' => $recordId,
            'user_type' => 'App\\Models\\User',
            'user_id' => 11,
            'event' => $event,
            'old_values' => json_encode(array_map(fn ($change) => $change['old'], $changes)),
            'new_values' => json_encode(array_map(fn ($change) => $change['new'], $changes)),
            'url' => null,
            'ip_address' => null,
            'user_agent' => 'ImportEntriesReferencesAuto Command',
            'tags' => 'enrichment,organism-metadata-import',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Bulk insert collected audit records.
     */
    protected function insertAuditRecords(): void
    {
        if (empty($this->auditRecords)) {
            return;
        }

        try {
            $count = count($this->auditRecords);
            $chunks = array_chunk($this->auditRecords, 500);
            foreach ($chunks as $chunk) {
                DB::table('audits')->insert($chunk);
            }
            Log::info("Inserted {$count} audit records.");
        } catch (\Exception $e) {
            Log::error('Failed to insert audit records: '.$e->getMessage());
        }

        // Free memory after flushing
        $this->auditRecords = [];
    }
}
