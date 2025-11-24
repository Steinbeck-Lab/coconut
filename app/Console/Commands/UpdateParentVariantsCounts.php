<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateParentVariantsCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coconut:update-parent-variants-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update variants_count column for parent molecules based on the number of variants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting the update process for parent molecules variants_count...');

        // Get counts of variants for each parent molecule
        $variantCounts = DB::table('molecules')
            ->select(DB::raw('parent_id, COUNT(*) as variant_count'))
            ->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->get();

        $this->info("Found {$variantCounts->count()} parent molecules with variants.");

        $progressBar = $this->output->createProgressBar($variantCounts->count());
        $progressBar->start();

        $updatedCount = 0;

        // Update each parent molecule's variants_count and has_variants flag
        foreach ($variantCounts as $count) {
            Molecule::where('id', $count->parent_id)
                ->update([
                    'variants_count' => $count->variant_count,
                    'has_variants' => true,
                ]);

            $updatedCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Updated variants_count and has_variants flag for {$updatedCount} parent molecules.");

        // Reset variants_count to 0 for parent molecules without variants
        $this->info('Resetting variants_count and has_variants flag for parent molecules without variants...');

        // Use a subquery to avoid parameter limits with whereNotIn
        $resetCount = DB::table('molecules')
            ->where('is_parent', true)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('molecules as variants')
                    ->whereColumn('variants.parent_id', 'molecules.id');
            })
            ->update([
                'variants_count' => 0,
                'has_variants' => false,
            ]);

        $this->info("Reset variants_count to 0 and has_variants to false for {$resetCount} parent molecules without variants.");

        $this->info('Update process completed successfully.');
    }
}
