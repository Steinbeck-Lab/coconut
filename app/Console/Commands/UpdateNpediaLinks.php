<?php

namespace App\Console\Commands;

use App\Models\Collection;
use App\Models\Entry;
use App\Models\Molecule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Events\AuditCustom;

class UpdateNpediaLinks extends Command
{
    private const COLLECTION_TITLE = 'NPEdia';

    private const OLD_ENTRY_LINK_PREFIX = 'http://www.cbrg.riken.jp/npedia/results.php?ID=';

    private const NEW_ENTRY_LINK_PREFIX = 'https://npedia.riken.jp/npedia/details.php?ID=';

    private const OLD_COLLECTION_URL = 'http://www.cbrg.riken.jp/npedia/';

    private const NEW_COLLECTION_URL = 'https://npedia.riken.jp/npedia/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:update-npedia-links
                            {--dry-run : Preview counts and sample changes without writing}
                            {--batch=500 : Number of records to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update NPEdia source links to the new RIKEN domain (issue #690) with audit trail';

    public function handle(): int
    {
        $collection = Collection::where('title', self::COLLECTION_TITLE)->first();

        if (! $collection) {
            $this->error('NPEdia collection not found.');

            return self::FAILURE;
        }

        $batchSize = max(1, (int) $this->option('batch'));
        $dryRun = (bool) $this->option('dry-run');

        $entryCount = Entry::query()
            ->where('collection_id', $collection->id)
            ->where('link', 'like', self::OLD_ENTRY_LINK_PREFIX.'%')
            ->count();

        $pivotCount = DB::table('collection_molecule')
            ->where('collection_id', $collection->id)
            ->where('url', 'like', '%'.self::OLD_ENTRY_LINK_PREFIX.'%')
            ->count();

        $collectionNeedsUpdate = $collection->url === self::OLD_COLLECTION_URL;

        $this->info("NPEdia collection ID: {$collection->id}");
        $this->info('Entries to update: '.$entryCount);
        $this->info('Collection-molecule pivots to update: '.$pivotCount);
        $this->info('Collection URL to update: '.($collectionNeedsUpdate ? 'yes' : 'no'));

        if ($dryRun) {
            $this->warn('Dry run enabled — no changes were written.');

            $sampleEntry = Entry::query()
                ->where('collection_id', $collection->id)
                ->where('link', 'like', self::OLD_ENTRY_LINK_PREFIX.'%')
                ->first(['reference_id', 'link']);

            if ($sampleEntry) {
                $this->line('Sample entry link:');
                $this->line('  old: '.$sampleEntry->link);
                $this->line('  new: '.$this->replaceEntryLink($sampleEntry->link));
            }

            $samplePivot = DB::table('collection_molecule')
                ->where('collection_id', $collection->id)
                ->where('url', 'like', '%'.self::OLD_ENTRY_LINK_PREFIX.'%')
                ->value('url');

            if ($samplePivot) {
                $this->line('Sample pivot url:');
                $this->line('  old: '.$samplePivot);
                $this->line('  new: '.$this->replaceEntryLink($samplePivot));
            }

            if ($collectionNeedsUpdate) {
                $this->line('Collection url:');
                $this->line('  old: '.self::OLD_COLLECTION_URL);
                $this->line('  new: '.self::NEW_COLLECTION_URL);
            }

            return self::SUCCESS;
        }

        try {
            $this->updateCollectionUrl($collection, $collectionNeedsUpdate);
            $entriesUpdated = $this->updateEntries($collection->id, $batchSize, $entryCount);
            $pivotsUpdated = $this->updateCollectionMoleculePivots($collection->id, $batchSize, $pivotCount);

            $this->info('Updated collection URL: '.($collectionNeedsUpdate ? 'yes' : 'skipped (already current)'));
            $this->info("Updated {$entriesUpdated} entries.");
            $this->info("Updated {$pivotsUpdated} collection-molecule pivots.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('NPEdia link update failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            $this->error('Update failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function updateCollectionUrl(Collection $collection, bool $needsUpdate): void
    {
        if (! $needsUpdate) {
            return;
        }

        $collection->url = self::NEW_COLLECTION_URL;
        $collection->save();
    }

    private function updateEntries(int $collectionId, int $batchSize, int $total): int
    {
        $updated = 0;
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        Entry::query()
            ->where('collection_id', $collectionId)
            ->where('link', 'like', self::OLD_ENTRY_LINK_PREFIX.'%')
            ->orderBy('id')
            ->chunkById($batchSize, function ($entries) use (&$updated, $progressBar) {
                DB::transaction(function () use ($entries, &$updated, $progressBar) {
                    foreach ($entries as $entry) {
                        $newLink = $this->replaceEntryLink($entry->link);

                        if ($newLink === $entry->link) {
                            $progressBar->advance();

                            continue;
                        }

                        $entry->link = $newLink;
                        $entry->meta_data = $this->replaceMetaDataUrl($entry->meta_data);
                        $entry->save();
                        $updated++;
                        $progressBar->advance();
                    }
                });
            });

        $progressBar->finish();
        $this->newLine();

        return $updated;
    }

    private function updateCollectionMoleculePivots(int $collectionId, int $batchSize, int $total): int
    {
        $updated = 0;
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        DB::table('collection_molecule')
            ->where('collection_id', $collectionId)
            ->where('url', 'like', '%'.self::OLD_ENTRY_LINK_PREFIX.'%')
            ->orderBy('molecule_id')
            ->chunk($batchSize, function ($pivots) use ($collectionId, &$updated, $progressBar) {
                DB::transaction(function () use ($pivots, $collectionId, &$updated, $progressBar) {
                    $bulkUpdates = [];
                    $auditEvents = [];

                    $moleculeIds = $pivots->pluck('molecule_id')->all();
                    $molecules = Molecule::query()->findMany($moleculeIds)->keyBy('id');

                    foreach ($pivots as $pivot) {
                        $newUrl = $this->replaceEntryLink($pivot->url);

                        if ($newUrl === $pivot->url) {
                            continue;
                        }

                        $bulkUpdates[] = [
                            'molecule_id' => $pivot->molecule_id,
                            'collection_id' => $collectionId,
                            'url' => $newUrl,
                            'reference' => $pivot->reference,
                        ];

                        $molecule = $molecules->get($pivot->molecule_id);
                        if ($molecule) {
                            $auditEvents[] = [
                                'molecule' => $molecule,
                                'old' => ['url' => $pivot->url],
                                'new' => ['url' => $newUrl],
                            ];
                        }

                        $updated++;
                        $progressBar->advance();
                    }

                    foreach (array_chunk($bulkUpdates, 100) as $chunk) {
                        DB::table('collection_molecule')->upsert(
                            $chunk,
                            ['molecule_id', 'collection_id'],
                            ['url']
                        );
                    }

                    foreach (array_chunk($auditEvents, 50) as $chunk) {
                        foreach ($chunk as $audit) {
                            $molecule = $audit['molecule'];
                            $molecule->auditEvent = 'npediaLinkUpdate';
                            $molecule->isCustomEvent = true;
                            $molecule->auditCustomOld = $audit['old'];
                            $molecule->auditCustomNew = $audit['new'];
                            Event::dispatch(AuditCustom::class, [$molecule]);
                        }
                    }
                });
            });

        $progressBar->finish();
        $this->newLine();

        return $updated;
    }

    private function replaceEntryLink(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return str_replace(self::OLD_ENTRY_LINK_PREFIX, self::NEW_ENTRY_LINK_PREFIX, $value);
    }

    /**
     * @param  array<string, mixed>|null  $metaData
     * @return array<string, mixed>|null
     */
    private function replaceMetaDataUrl(?array $metaData): ?array
    {
        if (! is_array($metaData) || ! isset($metaData['m']['url'])) {
            return $metaData;
        }

        $metaData['m']['url'] = $this->replaceEntryLink($metaData['m']['url']);

        return $metaData;
    }
}
