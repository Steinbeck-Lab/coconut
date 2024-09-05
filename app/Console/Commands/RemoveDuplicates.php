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
    protected $signature = 'coconut:molecules-remove-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes duplicate molecules from the database by merging associated data and marking duplicates.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Process flat molecules (non-parent, non-stereo)
        $this->processDuplicates(false, false, 'flat');

        // Process parent molecules (parent, non-stereo)
        $this->processDuplicates(true, false, 'parent');

        // Process stereo molecules (non-parent, stereo)
        $this->processDuplicates(false, true, 'stereo');
    }

    /**
     * Processes duplicate molecules based on the specified criteria.
     *
     * @param  bool|null  $isParent  Indicates whether to process parent molecules.
     * @param  bool|null  $hasStereo  Indicates whether to process molecules with stereo information.
     * @param  string  $type  Specifies the type of processing (flat, parent, or parents).
     */
    private function processDuplicates($isParent, $hasStereo, $type)
    {
        // Build query based on the criteria
        $query = DB::table('molecules')
            ->select('canonical_smiles', DB::raw('COUNT(*) as count'))
            ->groupBy('canonical_smiles')
            ->having(DB::raw('COUNT(*)'), '>', 1);

        if ($isParent !== null) {
            $query->where('is_parent', $isParent);
        }
        if ($hasStereo !== null) {
            $query->where('has_stereo', $hasStereo);
        }

        $results = $query->get();

        // Output the number of results
        echo count($results)."\r\n";

        // Loop through duplicates and process them
        foreach ($results as $result) {
            echo 'Canonical Smiles: '.$result->canonical_smiles.' - Count: '.$result->count."\n";

            $molecule = Molecule::where('canonical_smiles', $result->canonical_smiles)->first();
            $remainingMolecules = Molecule::where('canonical_smiles', $result->canonical_smiles)
                ->where('id', '!=', $molecule->id)
                ->get();

            if ($type == 'parents') {
                $this->resolveParents($molecule, $remainingMolecules);
            } else {
                $this->mergeData($molecule, $remainingMolecules, $type);
            }
        }
    }

    /**
     * Resolves parent molecules by reassigning variants from duplicates to the main entry.
     *
     * @param  Molecule  $entry  The main molecule entry.
     * @param  \Illuminate\Support\Collection  $duplicates  The duplicate molecules to resolve.
     */
    private function resolveParents($entry, $duplicates)
    {
        DB::transaction(function () use ($entry, $duplicates) {
            echo $entry->identifier."\r\n";

            foreach ($duplicates as $duplicate) {
                $variants = $duplicate->variants();

                if ($variants->exists()) {
                    $variants->update(['parent_id' => $entry->id]);
                }

                $duplicate->is_duplicate = true;
                $duplicate->save();
            }
        });
    }

    /**
     * Merges data from duplicate molecules into the main entry.
     *
     * @param  Molecule  $entry  The main molecule entry.
     * @param  \Illuminate\Support\Collection  $duplicates  The duplicate molecules to merge.
     * @param  string  $type  Specifies the type of processing (flat, parent, or parents).
     */
    private function mergeData($entry, $duplicates, $type)
    {
        DB::transaction(function () use ($entry, $duplicates, $type) {
            $synonyms = $entry->synonyms ?? [];

            echo $entry->identifier."\r\n";

            foreach ($duplicates as $duplicate) {
                // Merge synonyms
                $synonyms = array_merge($synonyms, $duplicate->synonyms ?? []);

                // Update related entries
                $this->updateRelatedTables($duplicate->id, $entry->id);

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

    /**
     * Updates related tables to reassign data from duplicates to the main molecule.
     *
     * @param  int  $duplicateId  The ID of the duplicate molecule.
     * @param  int  $entryId  The ID of the main molecule.
     */
    private function updateRelatedTables($duplicateId, $entryId)
    {
        // Update entries table
        DB::table('entries')
            ->where('molecule_id', $duplicateId)
            ->update(['molecule_id' => $entryId]);

        // Update citables table
        DB::table('citables')
            ->where('citable_type', 'App\Models\Molecule')
            ->where('citable_id', $duplicateId)
            ->whereNotExists(function ($query) use ($entryId) {
                $query->select(DB::raw(1))
                    ->from('citables as c')
                    ->where('c.citable_type', 'App\Models\Molecule')
                    ->where('c.citable_id', $entryId);
            })
            ->update(['citable_id' => $entryId]);

        // Handle collection_molecule table
        $this->updateCollectionMolecule($duplicateId, $entryId);

        // Update geo_location_molecule table
        DB::table('geo_location_molecule')
            ->where('molecule_id', $duplicateId)
            ->whereNotExists(function ($query) use ($entryId) {
                $query->select(DB::raw(1))
                    ->from('geo_location_molecule as glm')
                    ->where('glm.molecule_id', $entryId);
            })
            ->update(['molecule_id' => $entryId]);

        // Update molecule_organism table
        DB::table('molecule_organism')
            ->where('molecule_id', $duplicateId)
            ->whereNotExists(function ($query) use ($entryId) {
                $query->select(DB::raw(1))
                    ->from('molecule_organism as mo')
                    ->where('mo.molecule_id', $entryId);
            })
            ->update(['molecule_id' => $entryId]);

        // Update molecule_related table
        DB::table('molecule_related')
            ->where('molecule_id', $duplicateId)
            ->whereNotExists(function ($query) use ($entryId) {
                $query->select(DB::raw(1))
                    ->from('molecule_related as mr')
                    ->where('mr.molecule_id', $entryId);
            })
            ->update(['molecule_id' => $entryId]);
    }

    /**
     * Updates the collection_molecule table by merging URLs and references.
     *
     * @param  int  $duplicateId  The ID of the duplicate molecule.
     * @param  int  $entryId  The ID of the main molecule.
     */
    private function updateCollectionMolecule($duplicateId, $entryId)
    {
        $duplicateEntry = DB::table('collection_molecule')
            ->where('molecule_id', $duplicateId)
            ->first();

        if ($duplicateEntry) {
            $existingEntry = DB::table('collection_molecule')
                ->where('molecule_id', $entryId)
                ->first();

            if ($existingEntry) {
                DB::table('collection_molecule')
                    ->where('molecule_id', $entryId)
                    ->update([
                        'url' => DB::raw("CONCAT(url, '|', '{$duplicateEntry->url}')"),
                        'reference' => DB::raw("CONCAT(reference, '|', '{$duplicateEntry->reference}')"),
                    ]);

                DB::table('collection_molecule')
                    ->where('molecule_id', $duplicateId)
                    ->delete();
            } else {
                DB::table('collection_molecule')
                    ->where('molecule_id', $duplicateId)
                    ->update([
                        'molecule_id' => $entryId,
                        'url' => $duplicateEntry->url,
                        'reference' => $duplicateEntry->reference,
                    ]);
            }
        }
    }
}
