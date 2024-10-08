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
    protected $signature = 'coconut:organism-dedupe';

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

        Organism::select('id', 'name', 'slug')
            ->chunk(1000, function ($organisms) {
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

            array_unshift($choices, 'Skip');

            $retainValue = select(
                'Select the record you want to retain:',
                $choices
            );
            $retainIndex = array_keys($choices, $retainValue)[0];
            if ($retainValue === 'Skip') {
                $this->info("Skipping...{$columnValue}.");

                continue;
            } else {
                $retainIndex = $retainIndex - 1;
                if (isset($group[$retainIndex])) {
                    $selectedOrganism = $group[$retainIndex];
                    $restOfTheOrganisms = $group->forget($retainIndex);

                    foreach ($restOfTheOrganisms as $removableOrganism) {
                        DB::transaction(function () use ($selectedOrganism, $removableOrganism) {
                            try {
                                $moleculeIds = $removableOrganism->molecules->pluck('id')->toArray();

                                $removableOrganism->auditDetach('molecules', $moleculeIds);
                                $selectedOrganism->auditSyncWithoutDetaching('molecules', $moleculeIds);

                                $removableOrganism->delete();
                                $selectedOrganism->refresh();
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

        }

        $this->info('Duplicate handling complete.');

        return 0;
    }
}
