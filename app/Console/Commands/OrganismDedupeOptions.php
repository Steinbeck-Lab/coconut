<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Organism;

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

        DB::statement("UPDATE organisms
        SET slug = LOWER(
            REGEXP_REPLACE(
                REGEXP_REPLACE(name, '^[^A-Za-z]+|[^A-Za-z]+$', '', 'g'),'[^A-Za-z]+|(?<=[a-z])(?=[A-Z])', '-', 'g'))
        WHERE slug = '' or slug is null;"
        );


        $this->info('Finding duplicate records...');
        // Query to find duplicates case-insensitively
        $duplicates = Organism::selectRaw('slug, COUNT(*) as count')
            ->whereRaw('molecule_count > 0')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug')->toArray();

        if (!count($duplicates)) {
            $this->info('No duplicates found.');
            return 0;
        }


        // Fetch all records that have duplicates
        $records = Organism::whereIn('slug', $duplicates)
            ->get();

        // Group records by the lowercase version of the duplicate column for easier processing
        $groupedRecords = $records->groupBy('slug');

        foreach ($groupedRecords as $columnValue => $group) {
            $this->info("Duplicate records found for: {$columnValue}");

            foreach ($group as $index => $record) {
                $this->info("{$index}: ID = {$record->id}, Column Value = {$record->name}, rank = {$record->rank}, molucule count = {$record->molecule_count}, IRI = {$record->iri}");
            }

            $retainIndex = $this->ask('Enter the number of the record you want to retain');

            if (is_numeric($retainIndex) && isset($group[$retainIndex])) {

                $selected_organism = $group[$retainIndex];
                $rest_of_the_organisms = $group->forget($retainIndex);

                foreach ($rest_of_the_organisms as $removable_organism) {

                    DB::transaction(function () use ($selected_organism, $removable_organism) {
                        DB::beginTransaction();
                        try {
                            $moleculeIds = $removable_organism->molecules->pluck('id')->toArray();
                            // $removable_organism = $this->getOwnerRecord();
                            $removable_organism->molecules()->detach($moleculeIds);

                            // foreach ($currentOrganism->sampleLocations as $location) {
                            //     $location->molecules()->detach($moleculeIds);
                            // }
                            // $newOrganism = Organism::findOrFail($data['org_id']);

                            // $locations = $livewire->mountedTableBulkActionData['locations'];
                            // if ($locations) {
                            //     $sampleLocations = SampleLocation::findOrFail($locations);
                            //     foreach ($sampleLocations as $location) {
                            //         $location->molecules()->syncWithoutDetaching($moleculeIds);
                            //     }
                            // }
                            $selected_organism->molecules()->syncWithoutDetaching($moleculeIds);

                            $removable_organism->refresh();
                            $selected_organism->refresh();

                            $removable_organism->molecule_count = $removable_organism->molecules()->count();
                            $removable_organism->save();
                            $selected_organism->molecule_count = $selected_organism->molecules()->count();
                            $selected_organism->save();

                            DB::commit();
                        } catch (\Exception $e) {
                            // Rollback the transaction in case of any error
                            DB::rollBack();
                            throw $e; // Optionally rethrow the exception
                        }
                    });
                }

                $this->info("Reassigned molecules to Organism {$selected_organism->name} with ID = {$selected_organism->id}");
            } else {
                $this->warn("Invalid selection. No records were deleted for {$columnValue}.");
            }
        }

        $this->info('Duplicate handling complete.');
        return 0;
    }
}
