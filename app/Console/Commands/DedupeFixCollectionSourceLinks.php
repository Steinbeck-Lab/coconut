<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
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
        $batchSize = 1000; // Number of molecules to process in each batch
        $data = []; // Array to store data for batch updating
        $db_links = [
            30 => 'https://bidd.group/NPASS/compound.php?compoundID=',
            42 => 'http://langelabtools.wsu.edu/nmr/record/',
            43 => 'http://132.230.102.198:8000/streptomedb/get_drugcard/',
            54 => 'https://go.drugbank.com/drugs/',
            58 => 'https://www.way2drug.com/phyto4health/compound_card.php?compound_id=',
        ];
        // $batchCount = 1;

        // // Process all molecules in chunks to avoid memory exhaustion
        // DB::table('collection_molecule')
        //     ->select('id', 'collection_id', 'molecule_id', 'url', 'reference')
        //     ->orderBy('id')
        //     ->chunk($batchSize, function ($collection_molecules) use (&$data, $db_links, &$batchCount) {
        //         $this->info('started batch ');
        //         if ($batchCount >= 29) {
        //             foreach ($collection_molecules as $collection_molecule) {
        //                 $url = null;
        //                 $reference = null;

        //                 // Get the source URL and reference for the molecule
        //                 $entries = DB::select("SELECT link, reference_id FROM entries WHERE collection_id = {$collection_molecule->collection_id} and molecule_id = {$collection_molecule->molecule_id};");

        //                 foreach ($entries as $index => $entry) {

        //                     if ($index == 0) {
        //                         switch ($collection_molecule->collection_id) {
        //                             case 30:
        //                             case 42:
        //                             case 43:
        //                             case 54:
        //                             case 58:
        //                                 $url = $db_links[$collection_molecule->collection_id].$entry->reference_id;
        //                                 break;
        //                             default:
        //                                 $url = $entry->link;
        //                                 break;
        //                         }
        //                         $reference = $entry->reference_id;
        //                     } else {
        //                         switch ($collection_molecule->collection_id) {
        //                             case 30:
        //                             case 42:
        //                             case 43:
        //                             case 54:
        //                             case 58:
        //                                 $url .= '|'.$db_links[$collection_molecule->collection_id].$entry->reference_id;
        //                                 break;
        //                             default:
        //                                 $url .= '|'.$entry->link;
        //                                 break;
        //                         }
        //                         $reference .= '|'.$entry->reference_id;
        //                     }
        //                 }

        //                 // Prepare data for batch update
        //                 array_push($data, [
        //                     'collection_id' => $collection_molecule->collection_id,
        //                     'molecule_id' => $collection_molecule->molecule_id,
        //                     'url' => $url,
        //                     'reference' => $reference,
        //                 ]);
        //             }

        //             // Update the database with the calculated scores in batch
        //             if (! empty($data)) {
        //                 $this->info('Updating batch '.$batchCount);
        //                 $this->updateBatch($data);
        //                 $data = []; // Reset the data array for the next batch
        //             }
        //         }
        //         $batchCount = $batchCount + 1;
        //     });

        // // Ensure any remaining data is updated after the last chunk
        // if (! empty($data)) {
        //     $this->updateBatch($data);
        // }

        // Process parent molecules
        $data = [];
        $batchCount = 1;
        $total_parent_molecules = DB::select('SELECT count(*) FROM molecules WHERE is_parent = true;')[0]->count;
        DB::table('molecules')
            ->select('id')
            ->where('is_parent', true)
            ->orderBy('id')
            ->chunk($batchSize, function ($parent_molecules) use (&$data, &$batchCount, $total_parent_molecules, $batchSize) {
                $this->info('started parent batch '.$batchCount.' of '.ceil($total_parent_molecules / $batchSize));
                // $ids_string = implode(',', $parent_molecules->pluck('id')->toArray());
                if ($batchCount >= 2) {
                    $patents_pivot_rows = DB::table('collection_molecule')
                        ->selectRaw('collection_id, molecule_id, url, reference')
                        ->whereIntegerInRaw('molecule_id', $parent_molecules->pluck('id')->toArray())
                        ->get();
                    $total_parent_molecules = count($patents_pivot_rows);

                    // $molecule_number = 1;
                    $progressBar = $this->output->createProgressBar($total_parent_molecules);
                    foreach ($patents_pivot_rows as $parent_pivot_row) {
                        $url = null;
                        $reference = null;
                        $children_ids = collect(DB::select("SELECT id FROM molecules WHERE parent_id = {$parent_pivot_row->molecule_id};"));
                        // $children_ids_string = implode(',', $children_ids->pluck('id')->toArray());

                        $children_pivot_rows = DB::table('collection_molecule')
                            ->selectRaw('collection_id, molecule_id, url, reference')
                            ->whereRaw('collection_id=?', [$parent_pivot_row->collection_id])
                            ->whereIntegerInRaw('molecule_id', $children_ids->pluck('id')->toArray())
                            ->get();

                        foreach ($children_pivot_rows as $children_pivot_row) {
                            if (! $parent_pivot_row->url) {
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
                        // $this->info($molecule_number .' of '.$total_parent_molecules);
                        // $molecule_number++;
                    }

                    // Update the database with the calculated scores in batch
                    if (! empty($data)) {
                        $this->info('Updating parent batch '.$batchCount);
                        $this->updateBatch($data);
                        $progressBar->finish();
                        $data = []; // Reset the data array for the next batch
                    }
                }

                $batchCount = $batchCount + 1;
            });

        // Ensure any remaining data is updated after the last chunk
        if (! empty($data)) {
            $this->updateBatch($data);
        }

        $this->info('Updated successfully.');
    }

    private function updateBatch(array $data)
    {
        try {
            DB::transaction(function () use ($data) {
                foreach ($data as $row) {
                    if ($row['molecule_id'] != 293023 && $row['molecule_id'] != 427142 && $row['molecule_id'] != 395531 && $row['molecule_id'] != 292092 && $row['molecule_id'] != 186065 && $row['molecule_id'] != 23452 && $row['molecule_id'] != 33176) {
                        $molecule = Molecule::find($row['molecule_id']);
                        if (! $molecule) {
                            dd($row);
                        }
                        $currentPivotData = $molecule->collections()->wherePivot('collection_id', $row['collection_id'])->first()->pivot->toArray();
                        $molecule->collections()->updateExistingPivot($row['collection_id'], ['url' => $row['url'], 'reference' => $row['reference']]);
                        $molecule->auditEvent = 'moleculeCollectionUpdate';
                        $molecule->isCustomEvent = true;
                        $molecule->auditCustomOld = [
                            'url' => $currentPivotData['url'],
                            'reference' => $currentPivotData['reference'],
                        ];
                        $molecule->auditCustomNew = [
                            'url' => $row['url'],
                            'reference' => $row['reference'],
                        ];
                        Event::dispatch(AuditCustom::class, [$molecule]);
                    }
                }
            });
        } catch (\Exception $e) {
            exec('afplay /System/Library/Sounds/Ping.aiff');
            dd($e);
        }
    }
}
