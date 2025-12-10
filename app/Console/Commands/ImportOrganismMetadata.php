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

        // Process in chunks
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
        });

        $bar->finish();

        // Insert audit records
        if (! empty($this->auditRecords)) {
            $this->insertAuditRecords();
        }

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
            Log::warning("No organisms found for entry {$entry->id}. Skipping.");

            return;
        }

        foreach ($organismsToProcess as $organismName) {
            if (empty($organismName)) {
                Log::warning("Empty organism name for entry {$entry->id}. Skipping.");

                continue;
            }

            $organismId = $this->findOrCreateOrganism($organismName);
            if (! $organismId) {
                Log::warning("Could not find or create organism '{$organismName}' for entry {$entry->id}. Skipping.");

                continue;
            }

            // Build the metadata JSON structure for this molecule-organism pair
            $metadata = $this->buildOrganismMetadata(
                $entry,
                $mappingStatus,
                $references,
                $organismName,
                $flatDois,
                $flatParts,
                $flatGeoLocations,
                $flatEcosystems
            );

            // Update or insert molecule_organism record
            $this->upsertMoleculeOrganism(
                $entry->molecule_id,
                $organismId,
                $entry->collection_id,
                $metadata
            );
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
                        $organisms[] = $org['name'];
                    }
                }
            }
        }

        // From flat array
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
        array $flatDois,
        array $flatParts,
        array $flatGeoLocations,
        array $flatEcosystems
    ): array {
        $collectionEntry = [
            'id' => $entry->collection_id,
            'map' => $mappingStatus,
            'refs' => [],
        ];

        if ($mappingStatus === 'ambiguous') {
            // For ambiguous, put everything in unresolved
            $collectionEntry['unres'] = [
                'cit_ids' => $this->resolveCitationIds($flatDois),
                'smp_ids' => $this->resolveSampleLocationIds($flatParts),
                'geo_ids' => $this->resolveGeoLocationIds($flatGeoLocations),
                'eco_ids' => $this->resolveEcosystemIds($flatEcosystems),
            ];
        } else {
            // For full/inferred, build structured references
            $collectionEntry['refs'] = $this->buildStructuredReferences(
                $references,
                $organismName
            );
        }

        // Build the complete metadata structure
        $metadata = [
            'cols' => [$collectionEntry],
            'cit_ids' => $this->resolveCitationIds($flatDois),
            'col_ids' => [$entry->collection_id],
            'smp_ids' => $this->resolveSampleLocationIds($flatParts),
            'geo_ids' => $this->resolveGeoLocationIds($flatGeoLocations),
            'eco_ids' => $this->resolveEcosystemIds($flatEcosystems),
        ];

        return $metadata;
    }

    /**
     * Build structured references array for full/inferred mapping.
     */
    protected function buildStructuredReferences(array $references, string $organismName): array
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

            if (! $matchingOrganism && empty($doi)) {
                continue;
            }

            $citationId = $this->findOrCreateCitation($doi);

            $refEntry = [
                'cit_id' => $citationId,
                'smp_ids' => [],
                'locs' => [],
            ];

            if ($matchingOrganism) {
                // Get sample location IDs from parts
                $parts = $matchingOrganism['parts'] ?? [];
                $refEntry['smp_ids'] = $this->resolveSampleLocationIds($parts);

                // Get locations with geo_location and ecosystems
                $locations = $matchingOrganism['locations'] ?? [];
                foreach ($locations as $loc) {
                    $geoLocationId = null;
                    if (! empty($loc['name'])) {
                        $geoLocationId = $this->findOrCreateGeoLocation($loc['name']);
                    }

                    $ecosystems = $loc['ecosystems'] ?? [];
                    $ecosystemIds = $this->resolveEcosystemIds($ecosystems);

                    $refEntry['locs'][] = [
                        'geo_id' => $geoLocationId,
                        'eco_ids' => $ecosystemIds,
                    ];
                }
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
                    'citation_ids' => json_encode($newMetadata['citation_ids'] ?? []),
                    'metadata' => json_encode($newMetadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->collectAudit('molecule_organism', $newId, [], [
                    'molecule_id' => $moleculeId,
                    'organism_id' => $organismId,
                    'collection_ids' => json_encode([$collectionId]),
                    'citation_ids' => json_encode($newMetadata['citation_ids'] ?? []),
                    'metadata' => json_encode($newMetadata),
                ], 'created');
            } catch (QueryException $e) {
                // Handle race condition
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
                    // Handle race condition
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
                    // Handle race condition or unique constraint violation
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
                    // Handle race condition or unique constraint violation
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
                // Handle race condition
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
                // Merge references for same collection
                $collectionsById[$col['id']]['refs'] = array_merge(
                    $collectionsById[$col['id']]['refs'] ?? [],
                    $col['refs'] ?? []
                );
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

    protected function resolveSampleLocationIds(array $parts): array
    {
        $ids = [];
        foreach ($parts as $part) {
            if (! empty($part)) {
                $id = $this->findOrCreateSampleLocation($part);
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

    protected function resolveEcosystemIds(array $ecosystems): array
    {
        $ids = [];
        foreach ($ecosystems as $ecosystem) {
            if (! empty($ecosystem)) {
                $id = $this->findOrCreateEcosystem($ecosystem);
                if ($id) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    // =======================
    // Find or Create Methods
    // =======================

    protected function findOrCreateCitation(string $doi): ?int
    {
        if (empty($doi)) {
            return null;
        }

        // Extract DOI if it's a URL or has extra text
        $doi = $this->extractDoi($doi);
        if (empty($doi)) {
            return null;
        }

        if (isset($this->citationCache[$doi])) {
            return $this->citationCache[$doi];
        }

        $existing = DB::selectOne('SELECT id FROM citations WHERE doi = ?', [$doi]);
        if ($existing) {
            $this->citationCache[$doi] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('citations')->insertGetId([
                'doi' => $doi,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->citationCache[$doi] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM citations WHERE doi = ?', [$doi]);
            if ($existing) {
                $this->citationCache[$doi] = $existing->id;

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

    protected function findOrCreateSampleLocation(string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = Str::title($name);

        if (isset($this->sampleLocationCache[$name])) {
            return $this->sampleLocationCache[$name];
        }

        $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ?', [$name]);
        if ($existing) {
            $this->sampleLocationCache[$name] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('sample_locations')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->sampleLocationCache[$name] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM sample_locations WHERE name = ?', [$name]);
            if ($existing) {
                $this->sampleLocationCache[$name] = $existing->id;

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

    protected function findOrCreateEcosystem(string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        if (isset($this->ecosystemCache[$name])) {
            return $this->ecosystemCache[$name];
        }

        $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ?', [$name]);
        if ($existing) {
            $this->ecosystemCache[$name] = $existing->id;

            return $existing->id;
        }

        try {
            $id = DB::table('ecosystems')->insertGetId([
                'name' => $name,
                'description' => 'Ecosystem imported from entry data',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->ecosystemCache[$name] = $id;

            return $id;
        } catch (QueryException $e) {
            usleep(50000);
            $existing = DB::selectOne('SELECT id FROM ecosystems WHERE name = ?', [$name]);
            if ($existing) {
                $this->ecosystemCache[$name] = $existing->id;

                return $existing->id;
            }
            throw $e;
        }
    }

    // ===================
    // Helper Methods
    // ===================

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
            $chunks = array_chunk($this->auditRecords, 500);
            foreach ($chunks as $chunk) {
                DB::table('audits')->insert($chunk);
            }
            $this->info('Inserted '.count($this->auditRecords).' audit records.');
        } catch (\Exception $e) {
            Log::error('Failed to insert audit records: '.$e->getMessage());
        }
    }
}
