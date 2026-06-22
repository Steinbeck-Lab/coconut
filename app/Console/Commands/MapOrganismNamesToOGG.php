<?php

namespace App\Console\Commands;

use App\Models\Organism;
use App\Services\OrganismTaxonomy\GnfMatchGate;
use App\Services\OrganismTaxonomy\OrganismCurationResolver;
use App\Services\OrganismTaxonomy\OrganismTaxonomyApplier;
use App\Services\OrganismTaxonomy\OrganismTaxonomyMapper;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MapOrganismNamesToOGG extends Command
{
    protected $signature = 'coconut:organisms-map-ogg
                            {--cleanup-orphans : Delete organisms that have no linked molecules}
                            {--dry-run : Resolve mappings without saving to the database}
                            {--limit= : Maximum number of organisms to process}
                            {--backfill : Re-enrich existing organisms missing exact GNF taxonomy (retrospective)}
                            {--force : Re-process every organism, replacing stored taxonomy when an exact match is found}
                            {--allow-fuzzy : Accept fuzzy or partial GNF matches (overrides ORGANISM_TAXONOMY_REQUIRE_EXACT_MATCH)}
                            {--taxonomy-only : Only refresh taxonomy metadata, do not change IRI or rank}
                            {--with-molecules-only : Only process organisms linked to at least one molecule}
                            {--batch-size= : Names per GNF verifier request (default: config organism_taxonomy.batch_size)}
                            {--parallel= : Concurrent GNF batch requests (default: config organism_taxonomy.parallel_requests)}
                            {--audit-patterns : Report curation patterns for organisms missing exact taxonomy (no API calls)}
                            {--no-apply-curation : Skip curation fallbacks when the primary name has no exact GNF match}
                            {--export-patterns= : Write audit CSV to this path (used with --audit-patterns)}';

    protected $description = 'Map organism names to taxonomy databases and store exact-match GNF classification metadata';

    public function handle(
        OrganismTaxonomyMapper $mapper,
        OrganismTaxonomyApplier $applier,
        OrganismCurationResolver $curationResolver,
    ): int {
        if ($this->option('allow-fuzzy')) {
            config(['services.organism_taxonomy.require_exact_gnf_match' => false]);
        }

        if ($this->option('cleanup-orphans')) {
            $deleted = $this->deleteOrphanOrganisms();

            if ($deleted > 0) {
                $this->warn("Deleted {$deleted} organism(s) without linked molecules.");
            }
        }

        $limit = $this->option('limit');
        $maxToProcess = is_numeric($limit) ? max(0, (int) $limit) : null;
        $taxonomyOnly = (bool) $this->option('taxonomy-only');
        $dryRun = (bool) $this->option('dry-run');
        $exactOnly = GnfMatchGate::fromConfig()->requiresExactMatch();

        $query = $this->buildOrganismQuery();

        if ($this->option('audit-patterns')) {
            $auditQuery = Organism::query()->orderBy('id');

            if ($this->option('with-molecules-only')) {
                $auditQuery->where('molecule_count', '>', 0);
            }

            return $this->auditCurationPatterns($auditQuery, $curationResolver);
        }

        $total = $maxToProcess !== null
            ? min($maxToProcess, $query->count())
            : $query->count();

        if ($total === 0) {
            $this->info('No organisms found for taxonomy mapping.');

            return self::SUCCESS;
        }

        $applyCuration = $this->shouldApplyCuration();

        $this->info("Processing {$total} organism(s)...");

        if ($applyCuration) {
            $this->comment('Curation enabled — names without an exact GNF match are retried with curated lookup variants.');
        } else {
            $this->comment('Curation disabled — only primary organism names will be used.');
        }

        if ($exactOnly) {
            $this->comment('Exact GNF matches only — fuzzy and partial results will be skipped.');
        }

        if ($dryRun) {
            $this->comment('Dry run enabled — no database changes will be saved.');
        }

        if ($taxonomyOnly) {
            $batchSize = $this->resolveBatchSize();
            $parallel = $this->resolveParallel();

            $this->comment(sprintf(
                'GNF verifier batching: %d names/request, %d parallel request(s).',
                $batchSize,
                $parallel,
            ));

            return $this->processTaxonomyOnlyBatched(
                $query,
                $mapper,
                $applier,
                $curationResolver,
                $dryRun,
                $exactOnly,
                $applyCuration,
                $maxToProcess,
                $total,
                $batchSize,
                $parallel,
            );
        }

        return $this->processSequentialMapping(
            $query,
            $mapper,
            $applier,
            $curationResolver,
            $dryRun,
            $exactOnly,
            $applyCuration,
            $maxToProcess,
            $total,
        );
    }

    private function processTaxonomyOnlyBatched(
        Builder $query,
        OrganismTaxonomyMapper $mapper,
        OrganismTaxonomyApplier $applier,
        OrganismCurationResolver $curationResolver,
        bool $dryRun,
        bool $exactOnly,
        bool $applyCuration,
        ?int $maxToProcess,
        int $total,
        int $batchSize,
        int $parallel,
    ): int {
        $mapped = 0;
        $curated = 0;
        $failed = 0;
        $cleared = 0;
        $processed = 0;

        $progress = $this->output->createProgressBar($total);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $progress->setMessage('Starting');
        $progress->start();

        $query->chunkById(500, function (Collection $organisms) use (
            $mapper,
            $applier,
            $curationResolver,
            $dryRun,
            $exactOnly,
            $applyCuration,
            $maxToProcess,
            $batchSize,
            $parallel,
            $progress,
            &$mapped,
            &$curated,
            &$failed,
            &$cleared,
            &$processed,
        ) {
            $pending = collect();

            foreach ($organisms as $organism) {
                /** @var Organism $organism */
                if ($maxToProcess !== null && $processed >= $maxToProcess) {
                    return false;
                }

                if ($this->shouldSkipEnrichedOrganism($organism)) {
                    continue;
                }

                $pending->push($organism);
                $processed++;
            }

            if ($pending->isEmpty()) {
                return null;
            }

            $waveSize = $batchSize * $parallel;

            foreach ($pending->chunk($waveSize) as $wave) {
                $candidateMap = $this->buildCandidateMap($wave, $curationResolver, $applyCuration);
                $uniqueNames = collect($candidateMap)->flatten()->unique()->values()->all();
                $nameBatches = collect($uniqueNames)->chunk($batchSize)->map(
                    fn (Collection $names): array => $names->values()->all(),
                )->values()->all();

                $progress->setMessage(sprintf('GNF lookup (%d names)', count($uniqueNames)));
                $taxonomies = $mapper->fetchVerifierTaxonomyParallel($nameBatches, $parallel);

                foreach ($wave as $organism) {
                    $resolved = $this->resolveTaxonomyForOrganism(
                        $organism,
                        $candidateMap[$organism->id] ?? [$organism->name],
                        $taxonomies,
                        $curationResolver,
                        $exactOnly,
                    );
                    $taxonomy = $resolved['taxonomy'] ?? null;

                    if ($taxonomy === null) {
                        if ($exactOnly && is_array($organism->taxonomy)) {
                            if (! $dryRun) {
                                $applier->clearTaxonomy($organism);
                                $organism->save();
                                $cleared++;
                            }

                            if ($this->output->isVerbose()) {
                                $this->warn("Cleared non-exact taxonomy: {$organism->name}");
                            }
                        } else {
                            $failed++;

                            if ($this->output->isVerbose()) {
                                $this->error("No exact GNF match: {$organism->name}");
                            }
                        }

                        $progress->advance();

                        continue;
                    }

                    if ($resolved['curated'] ?? false) {
                        $curated++;
                    }

                    if (! $dryRun) {
                        $applier->applyTaxonomyOnly($organism, $taxonomy);
                        $organism->save();
                    }

                    $mapped++;

                    if ($this->output->isVerbose()) {
                        $this->info(sprintf(
                            '%s %s → taxonomy (%s / Exact)%s',
                            $dryRun ? '[dry-run]' : 'Enriched',
                            $organism->name,
                            $taxonomy['data_source'] ?? 'GNF',
                            ($resolved['curated'] ?? false)
                                ? ' via '.$resolved['resolved_lookup'].' ['.($taxonomy['curation']['pattern_label'] ?? 'curation').']'
                                : '',
                        ));
                    }

                    $progress->advance();
                }
            }
        });

        $progress->setMessage('Done');
        $progress->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Finished: %d enriched (%d via curation), %d failed, %d cleared, %d processed.',
            $mapped,
            $curated,
            $failed,
            $cleared,
            $processed,
        ));

        return $failed > 0 && $mapped === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processSequentialMapping(
        Builder $query,
        OrganismTaxonomyMapper $mapper,
        OrganismTaxonomyApplier $applier,
        OrganismCurationResolver $curationResolver,
        bool $dryRun,
        bool $exactOnly,
        bool $applyCuration,
        ?int $maxToProcess,
        int $total,
    ): int {
        $mapped = 0;
        $curated = 0;
        $failed = 0;
        $skippedExact = 0;
        $cleared = 0;
        $processed = 0;

        $query->chunkById(100, function ($organisms) use (
            $mapper,
            $applier,
            $dryRun,
            $applyCuration,
            $maxToProcess,
            &$mapped,
            &$curated,
            &$failed,
            &$skippedExact,
            &$cleared,
            &$processed,
        ) {
            foreach ($organisms as $organism) {
                /** @var Organism $organism */
                if ($maxToProcess !== null && $processed >= $maxToProcess) {
                    return false;
                }

                if ($this->shouldSkipEnrichedOrganism($organism)) {
                    continue;
                }

                $processed++;

                $result = $mapper->mapOrganism($organism, $applyCuration);
                $hasMapping = $result->isMapped() || $result->taxonomy !== null;

                if (! $hasMapping) {
                    if (is_array($organism->taxonomy) && ! $dryRun) {
                        $applier->clearTaxonomy($organism);
                        $organism->save();
                        $cleared++;
                    }

                    $failed++;
                    $this->error("No exact GNF match: {$organism->name}");

                    continue;
                }

                if (! $dryRun) {
                    if ($result->taxonomy === null && is_array($organism->taxonomy)) {
                        $applier->clearTaxonomy($organism);
                        $cleared++;
                    }

                    $applier->apply($organism, $result);
                    $organism->save();
                }

                if ($result->isMapped() && $result->taxonomy === null) {
                    $skippedExact++;
                }

                if (is_array($result->taxonomy['curation'] ?? null)) {
                    $curated++;
                }

                $mapped++;
                $details = $result->matchType
                    ? "{$result->source}/{$result->matchType}"
                    : $result->source;

                $curationNote = is_array($result->taxonomy['curation'] ?? null)
                    ? ', curated via '.($result->taxonomy['curation']['resolved_lookup'] ?? 'lookup')
                    : '';

                $this->info(sprintf(
                    '%s %s → rank=%s (%s)%s%s',
                    $dryRun ? '[dry-run]' : 'Mapped',
                    $organism->name,
                    $result->rank ?? 'unknown',
                    $details,
                    $result->taxonomy ? ', taxonomy stored' : ', IRI only',
                    $curationNote,
                ));
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Finished: %d enriched (%d via curation), %d failed, %d IRI-only (no exact taxonomy), %d cleared, %d processed.',
            $mapped,
            $curated,
            $failed,
            $skippedExact,
            $cleared,
            $processed,
        ));

        return $failed > 0 && $mapped === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function auditCurationPatterns(Builder $query, OrganismCurationResolver $curationResolver): int
    {
        $limit = $this->option('limit');
        $maxRows = is_numeric($limit) ? max(0, (int) $limit) : null;

        $summary = [];
        $exportRows = [];
        $scanned = 0;
        $moleculeLinks = 0;

        foreach ($curationResolver->patternDefinitions() as $pattern => $definition) {
            $summary[$pattern] = [
                'label' => $definition->label,
                'fixable' => $definition->fixable,
                'count' => 0,
                'molecule_links' => 0,
            ];
        }

        $query->chunkById(500, function (Collection $organisms) use (
            $curationResolver,
            $maxRows,
            &$summary,
            &$exportRows,
            &$scanned,
            &$moleculeLinks,
        ) {
            foreach ($organisms as $organism) {
                /** @var Organism $organism */
                if ($maxRows !== null && $scanned >= $maxRows) {
                    return false;
                }

                if ($organism->hasExactTaxonomyEnrichment()) {
                    continue;
                }

                $scanned++;
                $classification = $curationResolver->classify($organism);
                $links = (int) ($organism->molecule_count ?? 0);

                $summary[$classification->pattern]['count']++;
                $summary[$classification->pattern]['molecule_links'] += $links;
                $moleculeLinks += $links;

                $candidates = $curationResolver->lookupCandidates($organism);

                $exportRows[] = [
                    $organism->id,
                    $organism->name,
                    $classification->pattern,
                    $classification->label,
                    $classification->fixable ? 'yes' : 'no',
                    $links,
                    $organism->iri,
                    implode(' | ', $candidates),
                ];
            }

            return null;
        });

        if ($scanned === 0) {
            $this->info('All scoped organisms already have exact GNF taxonomy.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Curation pattern audit (%d organism(s) missing exact taxonomy):', $scanned));
        $this->newLine();

        uasort($summary, fn (array $left, array $right): int => $right['count'] <=> $left['count']);

        $this->table(
            ['Pattern', 'Fixable', 'Organisms', 'Molecule links', 'Description'],
            collect($summary)
                ->filter(fn (array $row): bool => $row['count'] > 0)
                ->map(function (array $row, string $pattern) use ($curationResolver): array {
                    $definition = $curationResolver->patternDefinitions()[$pattern];

                    return [
                        $row['label'],
                        $row['fixable'] ? 'yes' : 'no',
                        number_format($row['count']),
                        number_format($row['molecule_links']),
                        $definition->description,
                    ];
                })
                ->values()
                ->all(),
        );

        $fixable = collect($summary)
            ->filter(fn (array $row): bool => $row['fixable'])
            ->sum('count');

        $this->newLine();
        $this->comment(sprintf(
            '%s of %s organisms appear auto-fixable via curation (covering %s molecule links).',
            number_format($fixable),
            number_format($scanned),
            number_format($moleculeLinks),
        ));
        $this->comment('Run with --backfill --taxonomy-only to enrich fixable organisms (curation is on by default).');

        $exportPath = $this->option('export-patterns');

        if (is_string($exportPath) && $exportPath !== '') {
            $this->exportPatternAuditCsv($exportPath, $exportRows);
            $this->info("Exported audit details to {$exportPath}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Organism>  $organisms
     * @return array<int, list<string>>
     */
    private function buildCandidateMap(
        Collection $organisms,
        OrganismCurationResolver $curationResolver,
        bool $applyCuration,
    ): array {
        $candidateMap = [];

        /** @var Organism $organism */
        foreach ($organisms as $organism) {
            $candidateMap[$organism->id] = $applyCuration
                ? $curationResolver->lookupCandidates($organism)
                : [$organism->name];
        }

        return $candidateMap;
    }

    /**
     * @param  list<string>  $candidates
     * @param  array<string, array<string, mixed>|null>  $taxonomies
     * @return array{taxonomy: array<string, mixed>|null, curated: bool, resolved_lookup: string|null}
     */
    private function resolveTaxonomyForOrganism(
        Organism $organism,
        array $candidates,
        array $taxonomies,
        OrganismCurationResolver $curationResolver,
        bool $exactOnly,
    ): array {
        $gate = GnfMatchGate::fromConfig();

        foreach ($candidates as $candidate) {
            $profile = $taxonomies[$candidate] ?? null;

            if ($profile === null) {
                continue;
            }

            if ($exactOnly && ! $gate->acceptsTaxonomyProfile($profile, $candidate)) {
                continue;
            }

            $classification = $curationResolver->classify($organism);
            $taxonomy = $curationResolver->annotateResolvedTaxonomy(
                $profile,
                $organism,
                $candidate,
                $classification,
            );

            $curated = isset($taxonomy['curation']);

            return [
                'taxonomy' => $taxonomy,
                'curated' => $curated,
                'resolved_lookup' => $candidate,
            ];
        }

        return [
            'taxonomy' => null,
            'curated' => false,
            'resolved_lookup' => null,
        ];
    }

    private function shouldApplyCuration(): bool
    {
        if ($this->option('no-apply-curation')) {
            return false;
        }

        return (bool) config('services.organism_taxonomy.apply_curation_on_miss', true);
    }

    /**
     * @param  list<list<string|int|null>>  $rows
     */
    private function exportPatternAuditCsv(string $path, array $rows): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error("Could not write audit CSV to {$path}");

            return;
        }

        fputcsv($handle, [
            'id',
            'name',
            'pattern',
            'pattern_label',
            'fixable',
            'molecule_count',
            'iri',
            'lookup_candidates',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }

    private function resolveBatchSize(): int
    {
        $batchSize = $this->option('batch-size');

        if (is_numeric($batchSize)) {
            return max(1, (int) $batchSize);
        }

        return max(1, (int) config('services.organism_taxonomy.batch_size', 25));
    }

    private function resolveParallel(): int
    {
        $parallel = $this->option('parallel');

        if (is_numeric($parallel)) {
            return max(1, (int) $parallel);
        }

        return max(1, (int) config('services.organism_taxonomy.parallel_requests', 4));
    }

    private function buildOrganismQuery(): Builder
    {
        $query = Organism::query()->orderBy('id');

        if ($this->option('with-molecules-only')) {
            $query->where('molecule_count', '>', 0);
        }

        if ($this->option('force')) {
            return $query;
        }

        if ($this->option('backfill')) {
            // Avoid JSON operators in SQL (breaks on SQL_ASCII databases with Unicode taxonomy).
            return $query->where(function (Builder $builder): void {
                $builder->needingTaxonomyEnrichment()
                    ->orWhereNotNull('taxonomy');
            });
        }

        return $query->where(function (Builder $builder): void {
            $builder->whereNull('iri')
                ->orWhereNull('taxonomy');
        });
    }

    private function shouldSkipEnrichedOrganism(Organism $organism): bool
    {
        return $this->option('backfill')
            && ! $this->option('force')
            && $organism->hasExactTaxonomyEnrichment();
    }

    private function deleteOrphanOrganisms(): int
    {
        $deleted = 0;

        Organism::query()
            ->doesntHave('molecules')
            ->orderBy('id')
            ->chunkById(500, function ($organisms) use (&$deleted) {
                /** @var Organism $organism */
                foreach ($organisms as $organism) {
                    $organism->delete();
                    $deleted++;
                }
            });

        return $deleted;
    }
}
