<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Events\AuditCustom;

class DedupeFixCollectionSourceLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:fix-collection-source-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dedupes and fixes source links for specific collections mentioned in the command';

    public function handle()
    {
        Schema::table('entries', function (Blueprint $table) {
            if (! $this->hasIndex('entries', 'idx_entries_collection_molecule')) {
                $table->index(['collection_id', 'molecule_id'], 'idx_entries_collection_molecule');
            }
        });

        Schema::table('molecules', function (Blueprint $table) {
            if (! $this->hasIndex('molecules', 'idx_molecules_is_parent')) {
                $table->index(['is_parent', 'id'], 'idx_molecules_is_parent');
            }
            if (! $this->hasIndex('molecules', 'idx_molecules_parent')) {
                $table->index('parent_id', 'idx_molecules_parent');
            }
        });

        Schema::table('collection_molecule', function (Blueprint $table) {
            if (! $this->hasIndex('collection_molecule', 'idx_collection_molecule_molecule')) {
                $table->index('molecule_id', 'idx_collection_molecule_molecule');
            }
            if (! $this->hasIndex('collection_molecule', 'fk_collection_molecule_collection_id')) {
                $table->index('collection_id', 'fk_collection_molecule_collection_id');
            }
            if (! $this->hasIndex('collection_molecule', 'idx_molecule_collection_composite')) {
                $table->index(['molecule_id', 'collection_id'], 'idx_molecule_collection_composite');
            }
        });
        $this->info('Created indexes.');

        $batchSize = 10000; // Number of molecules to process in each batch
        $data = []; // Array to store data for batch updating
        $db_links = [
            30 => 'https://bidd.group/NPASS/compound.php?compoundID=',
            42 => 'http://langelabtools.wsu.edu/nmr/record/',
            43 => 'http://132.230.102.198:8000/streptomedb/get_drugcard/',
            54 => 'https://go.drugbank.com/drugs/',
            58 => 'https://www.way2drug.com/phyto4health/compound_card.php?compound_id=',
        ];
        $startTime = now();
        $childBatchCount = 1;

        // Get total records and calculate total batches
        $totalRecords = DB::table('collection_molecule')->count();
        $totalBatches = ceil($totalRecords / $batchSize);

        $this->info("Total batches to process: {$totalBatches}");

        // Create the main progress bar
        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');

        DB::table('collection_molecule')
            ->select('id', 'collection_id', 'molecule_id', 'url', 'reference')
            ->orderBy('id')
            ->chunk($batchSize, function ($collection_molecules) use (&$data, $db_links, &$childBatchCount, $progressBar, $totalBatches, $startTime) {
                $this->info("\nProcessing batch {$childBatchCount} of {$totalBatches}");
                $this->info('Time elapsed: '.$startTime->diffForHumans(now(), true));

                if ($childBatchCount >= 0) {
                    foreach ($collection_molecules as $collection_molecule) {
                        // $collection_molecule->collection_id = 30;
                        // $collection_molecule->molecule_id = 36269;
                        $url = null;
                        $reference = null;

                        // Get the source URL and reference for the molecule
                        $entries = DB::select("SELECT link, reference_id FROM entries WHERE collection_id = {$collection_molecule->collection_id} and molecule_id = {$collection_molecule->molecule_id};");

                        foreach ($entries as $index => $entry) {
                            // dd($index, $entry);
                            if ($index == 0) {
                                switch ($collection_molecule->collection_id) {
                                    case 30:
                                    case 42:
                                    case 43:
                                    case 54:
                                    case 58:
                                        $url = $db_links[$collection_molecule->collection_id].$entry->reference_id;
                                        break;
                                    default:
                                        $url = $entry->link;
                                        break;
                                }
                                $reference = $entry->reference_id;
                            } else {
                                switch ($collection_molecule->collection_id) {
                                    case 30:
                                    case 42:
                                    case 43:
                                    case 54:
                                    case 58:
                                        $url .= '|'.$db_links[$collection_molecule->collection_id].$entry->reference_id;
                                        break;
                                    default:
                                        $url .= '|'.$entry->link;
                                        break;
                                }
                                $reference .= '|'.$entry->reference_id;
                            }
                        }

                        array_push($data, [
                            'collection_id' => $collection_molecule->collection_id,
                            'molecule_id' => $collection_molecule->molecule_id,
                            'url' => $url,
                            'reference' => $reference,
                        ]);

                        $progressBar->advance();
                    }

                    if (! empty($data)) {
                        // $this->info("\nUpdating batch {$childBatchCount} of {$totalBatches}");
                        $this->updateBatch($data);
                        $data = [];
                    }
                }
                $childBatchCount = $childBatchCount + 1;
            });

        $progressBar->finish();
        $this->newLine();

        if (! empty($data)) {
            $this->info('Processing remaining records...');
            $this->updateBatch($data);
        }

        $this->info("\nTotal time taken: ".$startTime->diffForHumans(now(), true));
        $this->info('Process completed!');

        // Process parent molecules
        $data = [];
        $batchSize = 2000; // Number of molecules to process in each batch
        $parentBatchCount = 1;
        $total_parent_molecules = DB::select('SELECT count(*) FROM molecules WHERE is_parent = true;')[0]->count;
        DB::table('molecules')
            ->select('id')
            ->where('is_parent', true)
            ->orderBy('id')
            ->chunk($batchSize, function ($parent_molecules) use (&$data, &$parentBatchCount, $total_parent_molecules, $batchSize, $startTime) {
                $this->info("\nstarted parent batch ".$parentBatchCount.' of '.ceil($total_parent_molecules / $batchSize));
                $this->info('Time elapsed: '.$startTime->diffForHumans(now(), true));

                if ($parentBatchCount >= 0) {
                    $patents_pivot_rows = DB::table('collection_molecule')
                        ->selectRaw('collection_id, molecule_id, url, reference')
                        ->whereIntegerInRaw('molecule_id', $parent_molecules->pluck('id')->toArray())
                        ->get();

                    $total_parent_molecules = count($patents_pivot_rows);

                    $progressBar = $this->output->createProgressBar($total_parent_molecules);
                    foreach ($patents_pivot_rows as $parent_pivot_row) {
                        $url = $parent_pivot_row->url;
                        $reference = $parent_pivot_row->reference;

                        $children_pivot_rows = DB::select('
                                                    SELECT cm.collection_id, cm.molecule_id, cm.url, cm.reference 
                                                    FROM collection_molecule cm
                                                    INNER JOIN molecules m ON cm.molecule_id = m.id 
                                                    WHERE m.parent_id = ? 
                                                    AND cm.collection_id = ?
                                                ', [$parent_pivot_row->molecule_id, $parent_pivot_row->collection_id]);

                        foreach ($children_pivot_rows as $children_pivot_row) {
                            if (! $url) {
                                $url = $children_pivot_row->url;
                                $reference = $children_pivot_row->reference;
                            } else {
                                $url .= '|'.$children_pivot_row->url;
                                $reference .= '|'.$children_pivot_row->reference;
                            }
                        }
                        // push each parent data for update
                        array_push($data, [
                            'collection_id' => $parent_pivot_row->collection_id,
                            'molecule_id' => $parent_pivot_row->molecule_id,
                            'url' => $url,
                            'reference' => $reference,
                        ]);
                        $progressBar->advance();
                    }

                    // Update the database with the calculated scores in batch
                    if (! empty($data)) {
                        // $this->info('Updating parent batch ' . $parentBatchCount);
                        $this->updateBatch($data);
                        $progressBar->finish();
                        $data = []; // Reset the data array for the next batch
                    }
                }

                $parentBatchCount = $parentBatchCount + 1;
            });

        // Ensure any remaining data is updated after the last chunk
        if (! empty($data)) {
            $this->updateBatch($data);
        }

        Schema::table('entries', function (Blueprint $table) {
            $table->dropIndex('idx_entries_collection_molecule');
        });

        Schema::table('molecules', function (Blueprint $table) {
            $table->dropIndex('idx_molecules_is_parent');
            $table->dropIndex('idx_molecules_parent');
        });

        Schema::table('collection_molecule', function (Blueprint $table) {
            $table->dropIndex('idx_collection_molecule_molecule');
            $table->dropIndex('fk_collection_molecule_collection_id');
            $table->dropIndex('idx_molecule_collection_composite');
        });

        $this->info('Dropped indexes.');
        $this->info('Updated successfully.');
    }

    private function updateBatch(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                // Get all molecule IDs and collection IDs for efficient querying
                $moleculeIds = array_column($data, 'molecule_id');
                $collectionIds = array_column($data, 'collection_id');

                // Fetch all molecules and their pivot data in one go
                $molecules = Molecule::with(['collections' => function ($query) use ($collectionIds) {
                    $query->whereIn('collection_molecule.collection_id', $collectionIds);
                }])->findMany($moleculeIds);

                // Group data by molecule_id for easier access
                $dataByMolecule = collect($data)->groupBy('molecule_id');

                // Prepare bulk update data
                $bulkUpdates = [];
                $auditEvents = [];

                foreach ($molecules as $molecule) {
                    // Skip excluded molecules
                    if (in_array($molecule->id, [293023, 427142, 395531, 292092, 186065, 23452, 33176])) {
                        continue;
                    }

                    $updates = $dataByMolecule[$molecule->id];

                    foreach ($updates as $update) {
                        // Get current pivot data
                        $currentPivot = $molecule->collections
                            ->where('pivot.collection_id', $update['collection_id'])
                            ->first()
                            ->pivot;

                        // Skip if values are the same
                        if ($currentPivot->url === $update['url'] && $currentPivot->reference === $update['reference']) {
                            continue;
                        }

                        // Add to bulk updates
                        $bulkUpdates[] = [
                            'molecule_id' => $molecule->id,
                            'collection_id' => $update['collection_id'],
                            'url' => $update['url'],
                            'reference' => $update['reference'],
                        ];

                        // Prepare audit data
                        $auditEvents[] = [
                            'molecule' => $molecule,
                            'old' => [
                                'url' => $currentPivot->url,
                                'reference' => $currentPivot->reference,
                            ],
                            'new' => [
                                'url' => $update['url'],
                                'reference' => $update['reference'],
                            ],
                        ];
                    }
                }

                // Perform bulk updates in chunks
                foreach (array_chunk($bulkUpdates, 100) as $chunk) {
                    DB::table('collection_molecule')
                        ->upsert(
                            $chunk,
                            ['molecule_id', 'collection_id'],
                            ['url', 'reference']
                        );
                }

                // Process audit events in chunks
                foreach (array_chunk($auditEvents, 50) as $chunk) {
                    foreach ($chunk as $audit) {
                        $molecule = $audit['molecule'];
                        $molecule->auditEvent = 'moleculeCollectionUpdate';
                        $molecule->isCustomEvent = true;
                        $molecule->auditCustomOld = $audit['old'];
                        $molecule->auditCustomNew = $audit['new'];
                        Event::dispatch(AuditCustom::class, [$molecule]);
                    }
                }
            });
        } catch (\Exception $e) {
            \Log::error('Error in updateBatch: '.$e->getMessage(), [
                'exception' => $e,
                'data_count' => count($data),
            ]);
            throw $e;
        }
    }

    private function hasIndex($table, $indexName)
    {
        return collect(DB::select("
        SELECT indexname 
        FROM pg_indexes 
        WHERE tablename = '{$table}' 
        AND indexname = '{$indexName}'
    "))->isNotEmpty();
    }
}
