<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupInvalidStereochemistry extends Command
{
    protected $signature = 'coconut:cleanup-invalid-stereochemistry';

    protected $description = 'Cleanup molecules with invalid stereochemistry';

    public function handle()
    {
        try {
            DB::beginTransaction();

            $affectedMoleculeIds = DB::table('molecules')
                ->where('is_parent', false)
                ->where('identifier', 'LIKE', '%.0')
                ->where('canonical_smiles', 'LIKE', '%@%')
                ->pluck('id');

            if ($affectedMoleculeIds->isEmpty()) {
                $this->info('No molecules found.');
                DB::rollBack();

                return Command::SUCCESS;
            }

            // Update entries
            DB::table('entries')
                ->whereIn('molecule_id', $affectedMoleculeIds)
                ->update(['status' => 'PASSED']);

            // Update molecules
            DB::table('molecules')
                ->whereIn('id', $affectedMoleculeIds)
                ->update([
                    'status' => 'REVOKED',
                    'active' => false,
                    'comment' => json_encode([[
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                        'user_id' => 11,
                        'comment' => 'Molecule removed due to processing error.',
                    ]]),
                    'canonical_smiles' => DB::raw("canonical_smiles || '***'"),
                ]);

            // Delete collection_molecule relationships
            DB::table('collection_molecule')
                ->whereIn('molecule_id', $affectedMoleculeIds)
                ->delete();

            // Delete molecule_related relationships
            DB::table('molecule_related')
                ->where(function ($query) use ($affectedMoleculeIds) {
                    $query->whereIn('molecule_id', $affectedMoleculeIds)
                        ->orWhereIn('related_id', $affectedMoleculeIds);
                })
                ->delete();

            DB::commit();

            $this->info("Cleaned up {$affectedMoleculeIds->count()} molecules.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
