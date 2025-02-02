<?php

namespace App\Console\Commands;

use App\Models\Citation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FetchMissingCitationDetails extends Command
{
    protected $signature = 'coconut:fetch-missing-citation-details';

    protected $description = 'Goes through citations with missing details (must have DOI) and fills them';

    private array $failedDois = [];

    public function handle()
    {
        $this->info("\nChecking for citations with missing details...");

        $totalRecords = Citation::whereNotNull('doi')
            ->where(function ($query) {
                $query->whereNull('title')
                    ->orWhere('title', '')
                    ->orWhereNull('authors')
                    ->orWhere('authors', '')
                    ->orWhereNull('citation_text')
                    ->orWhere('citation_text', '');
            })->count();

        if ($totalRecords === 0) {
            $this->info('No citations with missing details found.');

            return;
        }

        $this->info("Found {$totalRecords} citations with missing details.");

        $batchSize = 100;
        $totalBatches = ceil($totalRecords / $batchSize);
        $batchCount = 1;
        $updatedCount = 0;
        $failedCount = 0;

        Citation::whereNotNull('doi')
            ->where(function ($query) {
                $query->whereNull('title')
                    ->orWhere('title', '')
                    ->orWhereNull('authors')
                    ->orWhere('authors', '')
                    ->orWhereNull('citation_text')
                    ->orWhere('citation_text', '');
            })
            ->select('id', 'doi', 'title', 'authors', 'citation_text')
            ->chunk($batchSize, function (Collection $citations) use (&$updatedCount, &$failedCount, &$batchCount, $totalBatches) {
                $this->info("\nProcessing batch {$batchCount} of {$totalBatches}");

                $progressBar = $this->output->createProgressBar(count($citations));
                $progressBar->setFormat('Batch %current%/%max% [%bar%] %percent:3s%%');
                $progressBar->start();

                DB::transaction(function () use ($citations, &$updatedCount, &$failedCount, $progressBar) {
                    $updates = [];
                    $audits = [];
                    $now = now();

                    foreach ($citations as $citation) {
                        try {
                            $citationDetails = fetchDOICitation(trim($citation->doi));

                            if ($citationDetails) {
                                // Store original values for audit
                                $oldValues = [
                                    'title' => $citation->title,
                                    'authors' => $citation->authors,
                                    'citation_text' => $citation->citation_text,
                                ];

                                $newValues = [
                                    'title' => $citationDetails['title'],
                                    'authors' => $citationDetails['authors'],
                                    'citation_text' => $citationDetails['citation_text'],
                                    'updated_at' => $now,
                                ];

                                // Only update if there are actual changes
                                $hasChanges = array_diff_assoc($newValues, [
                                    'title' => $citation->title,
                                    'authors' => $citation->authors,
                                    'citation_text' => $citation->citation_text,
                                ]);

                                if (! empty($hasChanges)) {
                                    $updates[] = array_merge(
                                        ['id' => $citation->id],
                                        $newValues
                                    );

                                    // Create audit record
                                    $audits[] = [
                                        'user_type' => 'App\Models\User',
                                        'user_id' => 11,
                                        'auditable_type' => 'App\Models\Citation',
                                        'auditable_id' => $citation->id,
                                        'event' => 'updated',
                                        'url' => 'artisan coconut:fetch-missing-citation-details',
                                        'ip_address' => request()->ip(),
                                        'user_agent' => 'Symfony',
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                        'old_values' => json_encode($oldValues),
                                        'new_values' => json_encode($newValues),
                                    ];
                                }

                                $updatedCount++;
                            } else {
                                $this->failedDois[] = $citation->doi;
                                $failedCount++;
                            }
                        } catch (\Exception $e) {
                            $this->failedDois[] = [$citation->id, $citation->doi, $e->getMessage()];
                            $failedCount++;
                            Log::error("Error updating citation details for DOI {$citation->doi}: ".$e->getMessage());
                        }

                        $progressBar->advance();
                    }

                    $this->info("\nStarting bulk update of citations...");
                    // Bulk update citations
                    if (! empty($updates)) {
                        foreach ($updates as $update) {
                            $updateData = [
                                'title' => $update['title'],
                                'authors' => $update['authors'],
                                'citation_text' => $update['citation_text'],
                                'updated_at' => $update['updated_at'],
                            ];
                            DB::table('citations')
                                ->where('id', $update['id'])
                                ->update($updateData);
                        }
                    }

                    $this->info("\nStarting bulk update of audits...");
                    // Bulk insert audits
                    if (! empty($audits)) {
                        DB::table('audits')->insert($audits);
                    }
                });

                $progressBar->finish();
                $this->line('');
                $batchCount++;
            });

        $this->info("\nUpdated $updatedCount citations with missing details");
        $this->info("Failed to update $failedCount citations");

        if (! empty($this->failedDois)) {
            File::put(
                storage_path('citations_fix_data/failed_citation_updates.json'),
                json_encode($this->failedDois, JSON_UNESCAPED_SLASHES)
            );
            $this->info('Failed DOIs have been written to failed_citation_updates.json');
        }
    }
}
