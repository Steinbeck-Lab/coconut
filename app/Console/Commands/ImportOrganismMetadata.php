<?php

namespace App\Console\Commands;

use App\Models\Entry;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportOrganismMetadata extends Command
{
    /**
     * Store IDs of successfully processed entries.
     */
    protected array $successfulEntryIds = [];

    /**
     * Store IDs of failed entries.
     */
    protected array $failedEntryIds = [];

    protected $signature = 'coconut:import-organism-metadata {collection? : Process only entries from this collection ID}';

    protected $description = 'Import organism metadata from entries into molecule_organism table with structured JSON';

    private int $batchSize = 5000;

    private array $citationCache = [];

    private array $organismCache = [];

    private array $sampleLocationCache = [];

    private array $geoLocationCache = [];

    private array $ecosystemCache = [];

    private array $auditRecords = [];

    public function handle(): int
    {
        $collectionId = $this->argument('collection');

        // Build query for entries with molecule_id and meta_data
        $query = DB::table('entries')
            ->whereNotNull('molecule_id')
            ->whereNotNull('meta_data')
            ->where('status', '=', 'AUTOCURATION');

        if ($collectionId) {
            $query->where('collection_id', '=', $collectionId);
            $this->info("Processing entries from collection ID: {$collectionId}");
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No entries found to process.');

            return self::SUCCESS;
        }

        $this->info("Processing {$totalCount} entries...");
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        // Use chunkById for efficient batch processing
        $query->orderBy('id')->chunkById($this->batchSize, function ($entries) use (&$successCount, &$failedCount, $bar) {
            // Reset entry ID arrays at the start of each batch
            $this->successfulEntryIds = [];
            $this->failedEntryIds = [];
            foreach ($entries as $entry) {
                try {
                    // Each entry in its own isolated transaction
                    DB::transaction(function () use ($entry) {
                        $this->processEntry($entry);
                    }, 1); // 1 attempt, no retries
                    $successCount++;
                    $this->successfulEntryIds[] = $entry->molecule_id;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $this->failedEntryIds[] = $entry->molecule_id;
                    $p = $e->getPrevious();
                    Log::error("Failed to process entry {$entry->id}", [
                        'error' => $e->getMessage(),
                        'class' => get_class($e),
                        'code' => $e->getCode(),
                        'prev_class' => $p ? get_class($p) : null,
                        'prev_code' => $p?->getCode(),
                        'prev' => $p?->getMessage(),
                    ]);
                }
                $bar->advance();
            }
            // Flush audit records after each batch to avoid memory exhaustion
            if (! empty($this->auditRecords)) {
                $this->insertAuditRecords();
            }

            // Fetch molecules for successful entries, excluding failed ones
            $successfulIds = array_diff($this->successfulEntryIds, $this->failedEntryIds);
            if (! empty($successfulIds)) {
                // Use a single raw SQL query for maximum efficiency
                $ids = implode(',', array_map('intval', $successfulIds));
                if (! empty($ids)) {
                    $sql = "UPDATE molecules m\n"
                            ."SET curation_status = (\n"
                            ."  jsonb_set(\n"
                            ."    COALESCE(m.curation_status::jsonb, '{}'::jsonb),\n"
                            ."    '{enrich-molecules}',\n"
                            ."    COALESCE(m.curation_status::jsonb -> 'enrich-molecules', '{}'::jsonb)\n"
                            ."      || jsonb_build_object(\n"
                            ."           'status', 'completed',\n"
                            ."           'processed_at', to_jsonb(\n"
                            ."             to_char(clock_timestamp() at time zone 'UTC',\n"
                            ."                     'YYYY-MM-DD\"T\"HH24:MI:SS.US\"Z\"')\n"
                            ."           ),\n"
                            ."           'error_message', 'null'::jsonb\n"
                            ."         ),\n"
                            ."    true\n"
                            ."  )\n"
                            .")::json\n"
                            ."WHERE m.id IN ($ids)\n"
                            ."AND COALESCE(m.curation_status::jsonb #>> '{enrich-molecules,status}', '') <> 'completed'";

                    DB::statement($sql);
                }
            }
        }, 'id');

        $bar->finish();

        return self::SUCCESS;
    }

    /**
     * Process a single entry and update molecule_organism records.
     */
    protected function processEntry(object $entry): void
    {
        $metaData = json_decode($entry->meta_data, true);
        if (! $metaData || ! isset($metaData['new_molecule_data'])) {
            Log::warning("Entry {$entry->id} has no valid meta_data. Skipping.");

            return;
        }

        $nmd = $metaData['new_molecule_data'];
        $mappingStatus = $nmd['mapping_status'] ?? 'ambiguous';
        $references = $nmd['references'] ?? [];

        // Get flat arrays for aggregation (these go into root-level arrays)
        $flatDois = $nmd['dois'] ?? [];
        $flatOrganisms = $nmd['organisms'] ?? [];
        $flatParts = $nmd['organism_parts'] ?? [];
        $flatGeoLocations = $nmd['geo_locations'] ?? [];
        $flatEcosystems = $nmd['ecosystems'] ?? [];

        // Process each organism from the references or flat arrays
        $organismsToProcess = $this->extractOrganismsFromReferences($references, $flatOrganisms);

        if (empty($organismsToProcess)) {
            DB::table('entries')->where('id', $entry->id)->update(['status' => 'IMPORTED']);

            return;
        }

        // Pre-resolve flat IDs once for reuse
        $resolvedCitIds = $this->resolveCitationIds($flatDois);

        $resolvedGeoIds = $this->resolveGeoLocationIds($flatGeoLocations);

        $resolvedEcoIds = $this->resolveEcosystemIds($flatEcosystems);

        foreach ($organismsToProcess as $index => $organismName) {

            if (empty($organismName)) {
                continue;
            }

            $organismId = $this->findOrCreateOrganism($organismName);
            if (! $organismId) {
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

            // Update or insert molecule_organism record
            $this->upsertMoleculeOrganism(
                $entry->molecule_id,
                $organismId,
                $entry->collection_id,
                $metadata
            );

            // Link organism to its specific geo_locations from references
            $organismGeoLocations = $this->extractOrganismGeoLocations($references, $organismName);
            if (! empty($organismGeoLocations)) {
                $this->processGeoLocationOrganism(
                    $organismId,
                    $organismGeoLocations
                );
            } elseif (! empty($flatGeoLocations)) {
                // Fallback to flat geo_locations for ambiguous/inferred with single organism
                $this->processGeoLocationOrganism(
                    $organismId,
                    $flatGeoLocations
                );
            }
        }

        // Process geo_location_molecule pivot table for this entry
        if (! empty($flatGeoLocations)) {
            $this->processGeoLocationMolecule(
                $entry->molecule_id,
                $flatGeoLocations,
                $flatEcosystems
            );
        }

        // Process citables (molecule-citation link) for this entry
        if (! empty($flatDois)) {
            $this->processCitables(
                $entry->molecule_id,
                $entry->collection_id,
                $flatDois
            );
        }

        // Link molecule to collection
        $this->linkMoleculeToCollection(
            $entry->molecule_id,
            $entry->collection_id,
            $nmd['link'] ?? null,
            $nmd['reference_id'] ?? null,
            $nmd['mol_filename'] ?? null,
            $nmd['structural_comments'] ?? null
        );

        // After processing, set entry status to IMPORTED
        DB::table('entries')->where('id', $entry->id)->update(['status' => 'IMPORTED']);
    }

    /**
     * Extract unique organism names from references and flat arrays.
     */
    protected function extractOrganismsFromReferences(array $references, array $flatOrganisms): array
    {
        $organisms = [];

        // From structured references
        foreach ($references as $ref) {
            if (isset($ref['organisms']) && is_array($ref['organisms'])) {
                foreach ($ref['organisms'] as $org) {
                    if (isset($org['name']) && ! empty($org['name'])) {
                        // Sanitize UTF-8 immediately upon extraction
                        $organisms[] = $this->sanitizeUtf8($org['name']);
                    }
                }
            }
        }

        // From flat array
        foreach ($flatOrganisms as $org) {
            if (! empty($org)) {
                // Sanitize UTF-8 immediately upon extraction
                $organisms[] = $this->sanitizeUtf8($org);
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
            // For ambiguous, put everything in unresolved
            $collectionEntry['unres'] = [
                'cit_ids' => $resolvedCitIds,
                'smp_ids' => $resolvedSmpIds,
                'geo_ids' => $resolvedGeoIds,
                'eco_ids' => $resolvedEcoIds,
            ];

            // Build the complete metadata structure with entry-wide flat IDs
            $metadata = [
                'cols' => [$collectionEntry],
                'cit_ids' => $resolvedCitIds,
                'col_ids' => [$entry->collection_id],
                'smp_ids' => $resolvedSmpIds,
                'geo_ids' => $resolvedGeoIds,
                'eco_ids' => $resolvedEcoIds,
            ];
        } else {
            // For full/inferred, build structured references
            $structuredRefs = $this->buildStructuredReferences(
                $references,
                $organismName,
                $organismId
            );
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

            // Build the complete metadata structure with organism-specific IDs
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
            foreach (($ref['organisms'] ?? []) as $org) {
                if (($org['name'] ?? '') === $organismName) {
                    foreach (($org['locations'] ?? []) as $loc) {
                        if (! empty($loc['name'])) {
                            $geoLocations[] = $loc['name'];
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
            $organisms = $ref['organisms'] ?? [];

            // Find organism data matching our organism name
            $matchingOrganism = null;
            foreach ($organisms as $org) {
                if (isset($org['name']) && $org['name'] === $organismName) {
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
            $parts = $matchingOrganism['parts'] ?? [];
            $refEntry['smp_ids'] = $this->resolveSampleLocationIds($parts, $organismId);

            // Get locations with geo_location and ecosystems
            $locations = $matchingOrganism['locations'] ?? [];
            foreach ($locations as $loc) {
                $geoLocationId = null;
                if (! empty($loc['name'])) {
                    $geoLocationId = $this->findOrCreateGeoLocation($loc['name']);
                }

                $ecosystems = $loc['ecosystems'] ?? [];
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

    /**
     * Upsert molecule_organism record with metadata.
     */
    protected function upsertMoleculeOrganism(
        int $moleculeId,
        int $organismId,
        int $collectionId,
        array $newMetadata
    ): void {
        // Check if record exists (using base unique constraint: molecule_id + organism_id)
        $existing = DB::selectOne(
            'SELECT * FROM molecule_organism WHERE molecule_id = ? AND organism_id = ? LIMIT 1',
            [$moleculeId, $organismId]
        );

        if ($existing) {
            // Merge metadata
            $existingMetadata = json_decode($existing->metadata ?? '{}', true);
            $mergedMetadata = $this->mergeMetadata($existingMetadata, $newMetadata);

            // Merge collection_ids and citation_ids
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

            // Insert new record
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

                if (! $this->isUniqueViolation($e)) {
                    Log::error('Unexpected DB error in molecule_organism insert (not unique violation)', [
                        'sqlstate' => $e->errorInfo[0] ?? null,
                        'msg' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                // Handle race condition - unique violation
                usleep(50000);
                $existing = DB::selectOne(
                    'SELECT * FROM molecule_organism WHERE molecule_id = ? AND organism_id = ? LIMIT 1',
                    [$moleculeId, $organismId]
                );
                if ($existing) {
                    // Retry as update
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

            // Prepare locations field (ecosystems as comma-separated string)
            $locationsText = ! empty($flatEcosystems) ? implode(', ', array_filter($flatEcosystems)) : null;

            // Check if record exists
            $existing = DB::selectOne(
                'SELECT * FROM geo_location_molecule WHERE molecule_id = ? AND geo_location_id = ?',
                [$moleculeId, $geoLocationId]
            );

            if ($existing) {
                // Update locations if different
                if ($existing->locations !== $locationsText) {
                    DB::table('geo_location_molecule')
                        ->where('id', $existing->id)
                        ->update([
                            'locations' => $locationsText,
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // Insert new record
                try {
                    DB::table('geo_location_molecule')->insert([
                        'molecule_id' => $moleculeId,
                        'geo_location_id' => $geoLocationId,
                        'locations' => $locationsText,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException $e) {

                    if (! $this->isUniqueViolation($e)) {
                        Log::error('Unexpected DB error in geo_location_molecule insert (not unique violation)', [
                            'sqlstate' => $e->errorInfo[0] ?? null,
                            'msg' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    // Handle race condition - unique violation
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

            // Check if record exists
            $existing = DB::selectOne(
                'SELECT * FROM geo_location_organism WHERE organism_id = ? AND geo_location_id = ?',
                [$organismId, $geoLocationId]
            );

            if (! $existing) {
                // Insert new record
                try {
                    DB::table('geo_location_organism')->insert([
                        'organism_id' => $organismId,
                        'geo_location_id' => $geoLocationId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    if (! $this->isUniqueViolation($e)) {
                        Log::error('Unexpected DB error in geo_location_organism insert (not unique violation)', [
                            'sqlstate' => $e->errorInfo[0] ?? null,
                            'msg' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    // Handle race condition - unique violation
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
                // Insert molecule-citation record
                try {
                    DB::table('citables')->insert([
                        'citation_id' => $citationId,
                        'citable_id' => $moleculeId,
                        'citable_type' => 'App\\Models\\Molecule',
                    ]);
                } catch (QueryException $e) {
                    if (! $this->isUniqueViolation($e)) {
                        Log::error('Unexpected DB error in citables insert for molecule (not unique violation)', [
                            'sqlstate' => $e->errorInfo[0] ?? null,
                            'msg' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    // Handle race condition - unique violation
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
                // Insert collection-citation record
                try {
                    DB::table('citables')->insert([
                        'citation_id' => $citationId,
                        'citable_id' => $collectionId,
                        'citable_type' => 'App\\Models\\Collection',
                    ]);
                } catch (QueryException $e) {
                    if (! $this->isUniqueViolation($e)) {
                        Log::error('Unexpected DB error in citables insert for collection (not unique violation)', [
                            'sqlstate' => $e->errorInfo[0] ?? null,
                            'msg' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    // Handle race condition - unique violation
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

    /**
     * Link molecule to collection in the collection_molecule pivot table.
     */
    protected function linkMoleculeToCollection(
        int $moleculeId,
        int $collectionId,
        ?string $url,
        ?string $reference,
        ?string $molFilename,
        ?string $structuralComments
    ): void {
        // Check if record exists
        $existing = DB::selectOne(
            'SELECT * FROM collection_molecule WHERE collection_id = ? AND molecule_id = ?',
            [$collectionId, $moleculeId]
        );

        if ($existing) {
            // Update if any fields have changed
            $needsUpdate = false;
            $updates = [];

            if ($existing->url !== $url) {
                $updates['url'] = $url;
                $needsUpdate = true;
            }
            if ($existing->reference !== $reference) {
                $updates['reference'] = $reference;
                $needsUpdate = true;
            }
            if ($existing->mol_filename !== $molFilename) {
                $updates['mol_filename'] = $molFilename;
                $needsUpdate = true;
            }
            if ($existing->structural_comments !== $structuralComments) {
                $updates['structural_comments'] = $structuralComments;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $updates['updated_at'] = now();
                DB::table('collection_molecule')
                    ->where('id', $existing->id)
                    ->update($updates);
            }
        } else {
            // Insert new record
            try {
                DB::table('collection_molecule')->insert([
                    'collection_id' => $collectionId,
                    'molecule_id' => $moleculeId,
                    'url' => $url,
                    'reference' => $reference,
                    'mol_filename' => $molFilename,
                    'structural_comments' => $structuralComments,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $e) {
                if (! $this->isUniqueViolation($e)) {
                    Log::error('Unexpected DB error in collection_molecule insert (not unique violation)', [
                        'sqlstate' => $e->errorInfo[0] ?? null,
                        'msg' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                // Handle race condition - unique violation
                usleep(50000);
                $existing = DB::selectOne(
                    'SELECT * FROM collection_molecule WHERE collection_id = ? AND molecule_id = ?',
                    [$collectionId, $moleculeId]
                );
                if (! $existing) {
                    Log::warning("Failed to insert collection_molecule for collection={$collectionId}, molecule={$moleculeId}: ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Merge two metadata structures, combining collections arrays.
     */
    protected function mergeMetadata(array $existing, array $new): array
    {
        // Merge collections by collection ID
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
                        // Merge smp_ids and locs for same citation
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

    // ===================
    // Resolution Methods
    // ===================

    public function resolveCitationIds(array $dois): array
    {
        $ids = [];
        foreach ($dois as $doi) {
            if (! empty($doi)) {
                try {
                    $id = $this->findOrCreateCitation($doi);
                    if ($id) {
                        $ids[] = $id;
                    }
                } catch (\Throwable $e) {
                    $p = $e->getPrevious();
                    Log::error("Citation resolution failed for DOI '{$doi}'", [
                        'e_class' => get_class($e),
                        'e_msg' => $e->getMessage(),
                        'e_code' => $e->getCode(),
                        'p_class' => $p ? get_class($p) : null,
                        'p_msg' => $p?->getMessage(),
                        'p_code' => $p?->getCode(),
                    ]);
                    throw $e; // <-- do not continue inside the transaction
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
                try {
                    $id = $this->findOrCreateSampleLocation($part, $organismId);
                    if ($id) {
                        $ids[] = $id;
                    }
                } catch (\Throwable $e) {
                    $p = $e->getPrevious();
                    Log::error("Sample location resolution failed for part '{$part}'", [
                        'e_class' => get_class($e),
                        'e_msg' => $e->getMessage(),
                        'e_code' => $e->getCode(),
                        'p_class' => $p ? get_class($p) : null,
                        'p_msg' => $p?->getMessage(),
                        'p_code' => $p?->getCode(),
                    ]);
                    throw $e; // <-- do not continue inside the transaction
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function resolveGeoLocationIds(array $locations): array
    {
        $ids = [];
        foreach ($locations as $location) {
            if (! empty($location)) {
                try {
                    $id = $this->findOrCreateGeoLocation($location);
                    if ($id) {
                        $ids[] = $id;
                    }
                } catch (\Throwable $e) {
                    $p = $e->getPrevious();
                    Log::error("Geo location resolution failed for '{$location}'", [
                        'e_class' => get_class($e),
                        'e_msg' => $e->getMessage(),
                        'e_code' => $e->getCode(),
                        'p_class' => $p ? get_class($p) : null,
                        'p_msg' => $p?->getMessage(),
                        'p_code' => $p?->getCode(),
                    ]);
                    throw $e; // <-- do not continue inside the transaction
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
                try {
                    $id = $this->findOrCreateEcosystem($ecosystem, $geoLocationId);
                    if ($id) {
                        $ids[] = $id;
                    }
                } catch (\Throwable $e) {
                    $p = $e->getPrevious();
                    Log::error("Ecosystem resolution failed for '".mb_substr((string) $ecosystem, 0, 50, 'UTF-8')."...'", [
                        'e_class' => get_class($e),
                        'e_msg' => $e->getMessage(),
                        'e_code' => $e->getCode(),
                        'p_class' => $p ? get_class($p) : null,
                        'p_msg' => $p?->getMessage(),
                        'p_code' => $p?->getCode(),
                    ]);
                    throw $e; // <-- do not continue inside the transaction
                }
            }
        }

        return array_values(array_unique($ids));
    }

    // =======================
    // Find or Create Methods
    // =======================

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
            if (! $this->isUniqueViolation($e)) {
                Log::error('Unexpected DB error in citations insert (not unique violation)', [
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'msg' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Handle race condition - unique violation
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
            if (! $this->isUniqueViolation($e)) {
                Log::error('Unexpected DB error in citations insert (not unique violation)', [
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'msg' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Handle race condition - unique violation
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

        // Sanitize UTF-8 encoding
        $name = $this->sanitizeUtf8($name);

        // Truncate very long organism names using mb_substr (max 255 characters for database)
        $maxLength = 255;
        if (mb_strlen($name, 'UTF-8') > $maxLength) {
            $originalLength = mb_strlen($name, 'UTF-8');
            $name = mb_substr($name, 0, $maxLength, 'UTF-8');
            Log::warning("Organism name truncated from {$originalLength} to {$maxLength} characters: ".mb_substr($name, 0, 50, 'UTF-8').'...');
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
            if (! $this->isUniqueViolation($e)) {
                Log::error('Unexpected DB error in organisms insert (not unique violation)', [
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'msg' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Handle race condition - unique violation
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM organisms WHERE name = ?', [$name]);
            if ($existing) {
                $this->organismCache[$name] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    protected function findOrCreateSampleLocation(string $name, ?int $organismId = null): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = Str::title($name);

        // If organism_id is provided, cache and search by name + organism_id
        $cacheKey = $organismId ? "{$name}_{$organismId}" : $name;

        if (isset($this->sampleLocationCache[$cacheKey])) {
            return $this->sampleLocationCache[$cacheKey];
        }

        // If organism_id is provided, find existing record for this organism
        if ($organismId) {
            $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ? AND organism_id = ?', [$name, $organismId]);
            if ($existing) {
                $this->sampleLocationCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
        } else {
            // Without organism_id, find any existing record with this name
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
            if (! $this->isUniqueViolation($e)) {
                Log::error('Unexpected DB error in sample_locations insert (not unique violation)', [
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'msg' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Handle race condition - unique violation
            usleep(50000);
            if ($organismId) {
                $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ? AND organism_id = ?', [$name, $organismId]);
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
            if (! $this->isUniqueViolation($e)) {
                Log::error('Unexpected DB error in geo_locations insert (not unique violation)', [
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'msg' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Handle race condition - unique violation
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

        // Sanitize UTF-8 encoding FIRST
        $name = $this->sanitizeUtf8($name);

        // If geo_location_id is provided, cache and search by name + geo_location_id
        $cacheKey = $geoLocationId ? "{$name}_{$geoLocationId}" : $name;

        if (isset($this->ecosystemCache[$cacheKey])) {
            return $this->ecosystemCache[$cacheKey];
        }

        // If geo_location_id is provided, find existing record for this geo_location
        if ($geoLocationId) {
            $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ? AND geo_location_id = ?', [$name, $geoLocationId]);
            if ($existing) {
                $this->ecosystemCache[$cacheKey] = $existing->id;

                return $existing->id;
            }
        } else {
            // Without geo_location_id, find any existing record with this name
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
            if (! $this->isUniqueViolation($e)) {
                Log::error('Unexpected DB error in ecosystems insert (not unique violation)', [
                    'sqlstate' => $e->errorInfo[0] ?? null,
                    'msg' => $e->getMessage(),
                ]);
                throw $e;
            }

            // Handle race condition - unique violation
            usleep(50000);
            if ($geoLocationId) {
                $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ? AND geo_location_id = ?', [$name, $geoLocationId]);
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

    // ===================
    // Helper Methods
    // ===================

    /**
     * Check if a QueryException is a unique constraint violation.
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        // Postgres unique_violation
        return ($e->errorInfo[0] ?? null) === '23505' || $e->getCode() === '23505';
    }

    /**
     * Sanitize string to ensure valid UTF-8 encoding.
     */
    protected function sanitizeUtf8(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Remove invalid UTF-8 byte sequences using iconv
        $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $input);
        if ($cleaned === false) {
            // Fallback: strip to ASCII only if iconv fails
            $cleaned = preg_replace('/[^\x20-\x7E\x0A\x0D\x09]/s', '', $input);
        }

        // Remove control characters except newline, carriage return, and tab
        $cleaned = preg_replace('/[^\P{C}\n\r\t]+/u', '', $cleaned) ?? $cleaned;

        return $cleaned ?: '?';
    }

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
            'user_id' => 11, // COCONUT curator user ID
            'event' => $event,
            'old_values' => json_encode(array_map(fn ($change) => $change['old'], $changes)),
            'new_values' => json_encode(array_map(fn ($change) => $change['new'], $changes)),
            'url' => null,
            'ip_address' => null,
            'user_agent' => 'ImportOrganismMetadata Command',
            'tags' => 'organism-metadata-import',
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
