<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // fetch duplicate parent molecules
        $fm_results = DB::table('molecules')
            ->select('canonical_smiles', DB::raw('COUNT(*) as count'))
            ->where('is_parent', false)
            ->where('has_stereo', false)
            ->groupBy('canonical_smiles')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        // loop duplicates
        foreach ($fm_results as $result) {
            //fetch first entry
            echo 'Canonical Smiles: '.$result->canonical_smiles.' - Count: '.$result->count."\n";
            $molecule = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->first();

            $remaining_molecules = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->where('id', '!=', $molecule->id)
                ->get();

            $this->merge_data($molecule, $remaining_molecules, 'flat');
        }
        echo "\r\n";
        echo "\r\n";

        // fetch duplicate parent molecules
        $pm_results = DB::table('molecules')
            ->select('canonical_smiles', DB::raw('COUNT(*) as count'))
            ->where('is_parent', true)
            ->where('has_stereo', false)
            ->groupBy('canonical_smiles')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        echo count($pm_results);
        echo "\r\n";

        // loop duplicates
        foreach ($pm_results as $result) {
            //fetch first entry
            echo 'Canonical Smiles: '.$result->canonical_smiles.' - Count: '.$result->count."\n";
            $molecule = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->first();

            $remaining_molecules = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->where('id', '!=', $molecule->id)
                ->get();

            $this->merge_data($molecule, $remaining_molecules, 'parent');
        }

        $sv_results = DB::table('molecules')
            ->select('canonical_smiles', DB::raw('COUNT(*) as count'))
            ->where('is_parent', false)
            ->where('has_stereo', true)
            ->groupBy('canonical_smiles')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();

        echo count($sv_results);
        echo "\r\n";

        // loop duplicates
        foreach ($sv_results as $result) {
            //fetch first entry
            echo 'Canonical Smiles: '.$result->canonical_smiles.' - Count: '.$result->count."\n";
            $molecule = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->first();

            $remaining_molecules = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->where('id', '!=', $molecule->id)
                ->get();

            $this->merge_data($molecule, $remaining_molecules, 'parent');
        }
    }

    public function merge_data($entry, $duplicates, $type)
    {
        DB::transaction(function () use ($entry, $duplicates, $type) {
            $synonyms = $entry->synonyms ? $entry->synonyms : [];
            echo $entry->identifier;
            echo "\r\n";
            foreach ($duplicates as $duplicate) {
                // append synonyms
                $duplicate_synonyms = $duplicate->synonyms ? $duplicate->synonyms : [];
                $synonyms = array_merge($synonyms, $duplicate_synonyms);

                // update entries table
                DB::table('entries')
                    ->select('id')
                    ->where('molecule_id', $duplicate->id)
                    ->update(['molecule_id' => $entry->id]);

                // update citables
                DB::table('citables')
                    ->where('citable_type', 'App\Models\Molecule')
                    ->where('citable_id', $duplicate->id)
                    ->whereNotExists(function ($query) use ($entry) {
                        $query->select(DB::raw(1))
                            ->from('citables as c')
                            ->where('c.citable_type', 'App\Models\Molecule')
                            ->where('c.citable_id', $entry->id);
                    })
                    ->update(['citable_id' => $entry->id]);

                $duplicateEntry = DB::table('collection_molecule')
                    ->where('molecule_id', $duplicate->id)
                    ->first();

                if ($duplicateEntry) {
                    $existingEntry = DB::table('collection_molecule')
                        ->where('molecule_id', $entry->id)
                        ->first();

                    if ($existingEntry) {
                        DB::table('collection_molecule')
                            ->where('molecule_id', $entry->id)
                            ->update([
                                'url' => DB::raw("CONCAT(url, '|', '{$duplicateEntry->url}')"),
                                'reference' => DB::raw("CONCAT(reference, '|', '{$duplicateEntry->reference}')"),
                            ]);

                        DB::table('collection_molecule')
                            ->where('molecule_id', $duplicate->id)
                            ->delete();
                    } else {
                        DB::table('collection_molecule')
                            ->where('molecule_id', $duplicate->id)
                            ->update([
                                'molecule_id' => $entry->id,
                                'url' => $duplicateEntry->url,
                                'reference' => $duplicateEntry->reference,
                            ]);
                    }
                }

                // update geo_location_molecule
                DB::table('geo_location_molecule')
                    ->where('molecule_id', $duplicate->id)
                    ->whereNotExists(function ($query) use ($entry) {
                        $query->select(DB::raw(1))
                            ->from('geo_location_molecule as glm')
                            ->where('glm.molecule_id', $entry->id);
                    })
                    ->update(['molecule_id' => $entry->id]);

                // update molecule_organism
                DB::table('molecule_organism')
                    ->where('molecule_id', $duplicate->id)
                    ->whereNotExists(function ($query) use ($entry) {
                        $query->select(DB::raw(1))
                            ->from('molecule_organism as mo')
                            ->where('mo.molecule_id', $entry->id);
                    })
                    ->update(['molecule_id' => $entry->id]);

                // update molecule_related
                DB::table('molecule_related')
                    ->where('molecule_id', $duplicate->id)
                    ->whereNotExists(function ($query) use ($entry) {
                        $query->select(DB::raw(1))
                            ->from('molecule_related as mr')
                            ->where('mr.molecule_id', $entry->id);
                    })
                    ->update(['molecule_id' => $entry->id]);

                if ($type == 'parents') {
                    $variants = $duplicate->variants();
                    $variants->update(['parent_id' => $entry->id]);
                }

                $duplicate->is_duplicate = true;
                $duplicate->save();
            }

            $entry->synonyms = $synonyms;
            $entry->save();
        });
    }
}
