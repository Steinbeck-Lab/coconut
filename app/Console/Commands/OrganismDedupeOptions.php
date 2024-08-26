<?php

namespace App\Console\Commands;

use App\Models\Organism;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;
use function Laravel\Prompts\select;

class OrganismDedupeOptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:organism-molecule-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and handle duplicate records in the Organisms (case-insensitive)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating missing slugs...');

        Organism::whereNotNull('slug')->chunk(100, function ($organisms) {
            $organisms->each(function ($organism) {
                $slug = Str::slug($organism->name);
                $organism->update(['slug' => $slug]);
            });
        });

        $this->info('Finding duplicate records...');

        // Query to find duplicates case-insensitively
        $duplicates = Organism::selectRaw('slug, COUNT(*) as count')
            ->whereRaw('molecule_count > 0')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug')
            ->toArray();

        if (count($duplicates) === 0) {
            $this->info('No duplicates found.');

            return 0;
        }

        // Fetch all records that have duplicates
        $records = Organism::whereIn('slug', $duplicates)->get();

        // Group records by the lowercase version of the duplicate column for easier processing
        $groupedRecords = $records->groupBy('slug');

        $this->info('Found '.count($groupedRecords).' duplicate records.');

        foreach ($groupedRecords as $columnValue => $group) {
            $this->info("Duplicate records found for: {$columnValue}");

            $choices = [];
            foreach ($group as $index => $record) {
                $choices[$index] = "ID = {$record->id}, Name = {$record->name}, Rank = {$record->rank}, Molecule Count = {$record->molecule_count}, IRI = {$record->iri}";
            }

            $retainValue = select(
                'Select the record you want to retain:',
                $choices
            );
            $retainIndex = array_keys($choices, $retainValue)[0];

            if (isset($group[$retainIndex])) {
                $selectedOrganism = $group[$retainIndex];
                $restOfTheOrganisms = $group->forget($retainIndex);

                foreach ($restOfTheOrganisms as $removableOrganism) {
                    DB::transaction(function () use ($selectedOrganism, $removableOrganism) {
                        try {
                            $moleculeIds = $removableOrganism->molecules->pluck('id')->toArray();

                            // $removableOrganism->molecules()->detach($moleculeIds);
                            // $selectedOrganism->molecules()->syncWithoutDetaching($moleculeIds);

                            $removableOrganism->auditDetach('molecules', $moleculeIds);
                            $selectedOrganism->auditSyncWithoutDetaching('molecules', $moleculeIds);

                            $removableOrganism->molecule_count = $removableOrganism->molecules()->count();
                            $removableOrganism->delete();
                            $selectedOrganism->molecule_count = $selectedOrganism->molecules()->count();
                            $selectedOrganism->save();

                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }
                    });
                }

                $this->info("Reassigned molecules to Organism {$selectedOrganism->name} with ID = {$selectedOrganism->id}");
            } else {
                $this->warn("Invalid selection. No records were deleted for {$columnValue}.");
            }
        }

        $this->info('Duplicate handling complete.');

        return 0;
    }
}
