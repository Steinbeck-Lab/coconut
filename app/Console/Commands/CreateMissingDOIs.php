<?php

namespace App\Console\Commands;

use App\Models\Citation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CreateMissingDOIs extends Command
{
    protected $signature = 'coconut:create-missing-dois 
                            {start_batch=1 : The batch number to start processing from}
                            {--cdoi=doi_replaced.tsv : Path to the DOI replacement TSV file}';

    protected $description = 'Goes through entries and creates missing DOIs in the Citations table.';

    private array $existingCitationDois = [];

    private array $existingCitables = [];

    private array $correctedDois = [];

    private array $problemDois = [];

    private array $failedCitations = [];

    private array $existingProblemDois = [];

    private array $existingFailedCitations = [];

    public function handle()
    {
        $this->loadCorrectedDois();
        $this->loadExistingCitations();
        $this->loadExistingCitables();

        $batchSize = 10000;
        $totalCitationsCreated = 0;

        $totalRecords = DB::table('entries')->where('status', 'PASSED')->count();
        $totalBatches = ceil($totalRecords / $batchSize);
        $batchCount = 1;

        $this->info("Total batches to process: {$totalBatches}");

        DB::table('entries')
            ->select('doi', 'molecule_id')
            ->where('status', '=', 'PASSED')
            ->orderBy('id')
            ->chunk($batchSize, function (Collection $entries) use (&$totalCitationsCreated, &$batchCount, $totalBatches) {
                $startBatch = (int) $this->argument('start_batch');
                if ($batchCount >= $startBatch) {
                    DB::transaction(function () use ($entries, &$totalCitationsCreated, $batchCount, $totalBatches) {
                        $this->info("\nProcessing batch {$batchCount} of {$totalBatches}");
                        $citationsCreatedCount = 0;
                        $failedCitationsCount = 0;

                        // Reset problem and failed arrays for this batch
                        $batchProblemDois = [];
                        $batchFailedCitations = [];

                        $progressBar = $this->output->createProgressBar(count($entries));
                        $progressBar->setFormat('Batch %current%/%max% [%bar%] %percent:3s%%');
                        $progressBar->start();

                        $newCitations = [];
                        $newCitablePairs = [];

                        foreach ($entries as $entry) {
                            foreach (explode('|', $entry->doi) as $doi) {
                                $this->processDoi(
                                    $doi,
                                    $entry,
                                    $newCitations,
                                    $newCitablePairs,
                                    $citationsCreatedCount,
                                    $failedCitationsCount,
                                    $batchProblemDois,
                                    $batchFailedCitations
                                );
                            }
                            $progressBar->advance();
                        }

                        // de-duplicate citations
                        $newCitations = array_unique($newCitations, SORT_REGULAR);

                        // Bulk insert new citations and their audits
                        if (! empty($newCitations)) {
                            $citationAudits = [];
                            $now = now();

                            // Create audit records for new citations
                            foreach ($newCitations as $citation) {
                                $citationAudits[] = [
                                    'user_type' => 'App\Models\User',
                                    'user_id' => 11,
                                    'auditable_type' => 'App\Models\Citation',
                                    'auditable_id' => null, // Will be updated after insert
                                    'event' => 'created',
                                    'url' => 'artisan coconut:create-missing-dois',
                                    'ip_address' => request()->ip(),
                                    'user_agent' => 'Symfony',
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                    'old_values' => json_encode([]),
                                    'new_values' => json_encode([
                                        'doi' => $citation['doi'],
                                        'title' => $citation['title'],
                                        'authors' => $citation['authors'],
                                        'citation_text' => $citation['citation_text'],
                                        'active' => $citation['active'],
                                    ]),
                                ];
                            }

                            // Insert citations first
                            DB::table('citations')->insert($newCitations);

                            // Get the IDs of newly inserted citations
                            $newCitationDois = array_column($newCitations, 'doi');
                            $citationIdMap = Citation::whereIn('doi', $newCitationDois)
                                ->pluck('id', 'doi')
                                ->toArray();

                            // Update existingCitationDois with new citations
                            $this->existingCitationDois = array_merge($this->existingCitationDois, $citationIdMap);

                            // Update citation audits with correct IDs and insert them
                            foreach ($citationAudits as $index => $audit) {
                                $citationDoi = $newCitations[$index]['doi'];
                                if (isset($citationIdMap[$citationDoi])) {
                                    $citationAudits[$index]['auditable_id'] = $citationIdMap[$citationDoi];
                                }
                            }
                            DB::table('audits')->insert($citationAudits);

                            // Prepare bulk insert for citables and audits
                            $citableRecords = [];
                            $auditRecords = [];
                            $now = now();

                            foreach ($newCitablePairs as $pair) {
                                if (isset($citationIdMap[$pair['doi']])) {
                                    $citationId = $citationIdMap[$pair['doi']];
                                    $citableKey = $this->getCitableKey($citationId, $pair['molecule_id']);

                                    // Only create citable and audit if relation doesn't exist
                                    if (! isset($this->existingCitables[$citableKey])) {
                                        $citableRecords[] = [
                                            'citation_id' => $citationId,
                                            'citable_type' => 'App\Models\Molecule',
                                            'citable_id' => $pair['molecule_id'],
                                        ];

                                        // Add audit records only for new relationships
                                        $auditRecords[] = [
                                            'user_type' => 'App\Models\User',
                                            'user_id' => 11,
                                            'auditable_type' => 'App\Models\Citation',
                                            'auditable_id' => $citationId,
                                            'event' => 'sync',
                                            'url' => 'artisan coconut:create-missing-dois',
                                            'ip_address' => request()->ip(),
                                            'user_agent' => 'Symfony',
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                            'old_values' => json_encode([]),
                                            'new_values' => json_encode([
                                                'molecules' => [$pair['molecule_id']],
                                            ]),
                                        ];

                                        $auditRecords[] = [
                                            'user_type' => 'App\Models\User',
                                            'user_id' => 11,
                                            'auditable_type' => 'App\Models\Molecule',
                                            'auditable_id' => $pair['molecule_id'],
                                            'event' => 'sync',
                                            'url' => 'artisan coconut:create-missing-dois',
                                            'ip_address' => request()->ip(),
                                            'user_agent' => 'Symfony',
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                            'old_values' => json_encode([]),
                                            'new_values' => json_encode([
                                                'citations' => [$citationId],
                                            ]),
                                        ];

                                        // Add to existing citables to prevent duplicates in future batches
                                        $this->existingCitables[$citableKey] = true;
                                    }
                                }
                            }

                            // Bulk insert citables and audits only if we have new relationships
                            if (! empty($citableRecords)) {
                                DB::table('citables')->insert($citableRecords);
                            }
                            if (! empty($auditRecords)) {
                                DB::table('audits')->insert($auditRecords);
                            }
                        }

                        $totalCitationsCreated += $citationsCreatedCount;

                        // Write this batch's results
                        $this->writeOutputFiles($batchProblemDois, $batchFailedCitations);

                        $progressBar->finish();
                        $this->line('');
                        $this->info('Citations created: '.$citationsCreatedCount);
                        $this->info('Failed citations: '.$failedCitationsCount);
                    });
                }
                $batchCount++;
            });

    }

    private function loadCorrectedDois(): void
    {
        $doiFile = $this->option('cdoi');
        $correctedDoisFile = storage_path('citations_fix_data/'.$doiFile);
        if (($handle = fopen($correctedDoisFile, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, "\t", '"')) !== false) {
                try {
                    $this->correctedDois[$row[0]] = $row[1] ?? null;
                } catch (\ValueError $e) {
                    Log::info('An error occurred: '.$e->getMessage());
                }
            }
            fclose($handle);
        }
    }

    private function loadExistingCitations(): void
    {
        $this->existingCitationDois = Citation::pluck('id', 'doi')->toArray();
    }

    private function loadExistingCitables(): void
    {
        // Load existing relationships into memory for quick lookup
        $existingRelations = DB::table('citables')
            ->where('citable_type', 'App\Models\Molecule')
            ->select('citation_id', 'citable_id')
            ->get();

        foreach ($existingRelations as $relation) {
            $this->existingCitables[$this->getCitableKey($relation->citation_id, $relation->citable_id)] = true;
        }
    }

    private function getCitableKey(int $citationId, int $moleculeId): string
    {
        return "{$citationId}-{$moleculeId}";
    }

    private function processDoi(
        string $doi,
        object $entry,
        array &$newCitations,
        array &$newCitablePairs,
        int &$citationsCreatedCount,
        int &$failedCitationsCount,
        array &$batchProblemDois,
        array &$batchFailedCitations
    ): void {
        // Check if DOI exists in citations
        if (isset($this->existingCitationDois[$doi])) {
            $citationId = $this->existingCitationDois[$doi];
            $citableKey = $this->getCitableKey($citationId, $entry->molecule_id);

            // Only add to pairs if relationship doesn't exist
            if (! isset($this->existingCitables[$citableKey])) {
                $newCitablePairs[] = ['doi' => $doi, 'molecule_id' => $entry->molecule_id];
            }

            return;
        }

        $dois = $this->correctedDois[$doi] ?? $doi;
        foreach (explode('|', $dois) as $singleDoi) {
            $singleDoi = trim($singleDoi);

            if (isset($this->existingCitationDois[$singleDoi])) {
                $citationId = $this->existingCitationDois[$singleDoi];
                $citableKey = $this->getCitableKey($citationId, $entry->molecule_id);

                // Only add to pairs if relationship doesn't exist
                if (! isset($this->existingCitables[$citableKey])) {
                    $newCitablePairs[] = ['doi' => $singleDoi, 'molecule_id' => $entry->molecule_id];
                }

                continue;
            }

            try {
                $citationDetails = fetchDOICitation($singleDoi);
                if (! $citationDetails) {
                    $batchProblemDois[$singleDoi][] = $entry;

                    continue;
                }
                if (isset($this->existingCitationDois[$citationDetails['doi']])) {
                    $citationId = $this->existingCitationDois[$citationDetails['doi']];
                    $citableKey = $this->getCitableKey($citationId, $entry->molecule_id);

                    // Only add to pairs if relationship doesn't exist
                    if (! isset($this->existingCitables[$citableKey])) {
                        $newCitablePairs[] = ['doi' => $citationDetails['doi'], 'molecule_id' => $entry->molecule_id];
                    }

                    continue;
                }

                // Check if this DOI is already in newCitations
                $exists = array_filter($newCitations, function ($citation) use ($citationDetails) {
                    return $citation['doi'] === $citationDetails['doi'];
                });

                if (empty($exists)) {
                    $newCitations[] = [
                        'doi' => $citationDetails['doi'],
                        'title' => $citationDetails['title'],
                        'authors' => $citationDetails['authors'],
                        'citation_text' => $citationDetails['citation_text'],
                        'active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $newCitablePairs[] = ['doi' => $citationDetails['doi'], 'molecule_id' => $entry->molecule_id];
                $citationsCreatedCount++;
            } catch (\Exception $e) {
                $entry = (array) $entry;
                $entry['error'] = $e->getMessage();
                $batchFailedCitations[$singleDoi][] = $entry;
                $failedCitationsCount++;
            }
        }
    }

    private function writeOutputFiles(array $problemDois, array $failedCitations): void
    {
        $startBatch = (int) $this->argument('start_batch');

        // Initialize files if starting from batch 1
        if ($startBatch === 1) {
            File::put(storage_path('citations_fix_data/problem_dois.json'), json_encode($problemDois));
            File::put(storage_path('citations_fix_data/failed_citations.json'), json_encode($failedCitations));

            return;
        }

        // For subsequent batches, merge with existing data
        $problemDoisPath = storage_path('citations_fix_data/problem_dois.json');
        $failedCitationsPath = storage_path('citations_fix_data/failed_citations.json');

        // Merge problem DOIs
        $existingProblemDois = [];
        if (File::exists($problemDoisPath)) {
            $existingProblemDois = json_decode(File::get($problemDoisPath), true) ?? [];
        }

        foreach ($problemDois as $doi => $entries) {
            if (! isset($existingProblemDois[$doi])) {
                $existingProblemDois[$doi] = [];
            }
            $existingProblemDois[$doi] = array_merge(
                $existingProblemDois[$doi],
                $entries
            );
        }

        // Merge failed citations
        $existingFailedCitations = [];
        if (File::exists($failedCitationsPath)) {
            $existingFailedCitations = json_decode(File::get($failedCitationsPath), true) ?? [];
        }

        foreach ($failedCitations as $doi => $entries) {
            if (! isset($existingFailedCitations[$doi])) {
                $existingFailedCitations[$doi] = [];
            }
            $existingFailedCitations[$doi] = array_merge(
                $existingFailedCitations[$doi],
                $entries
            );
        }

        // Write merged data back to files
        File::put($problemDoisPath, json_encode($existingProblemDois));
        File::put($failedCitationsPath, json_encode($existingFailedCitations));

        // Update fixed DOIs file
        $keys = array_unique(array_merge(
            array_keys($existingProblemDois),
            array_keys($existingFailedCitations)
        ));

        File::put(
            storage_path('citations_fix_data/problem_dois_fixed.json'),
            json_encode($keys, JSON_UNESCAPED_SLASHES)
        );
    }
}
