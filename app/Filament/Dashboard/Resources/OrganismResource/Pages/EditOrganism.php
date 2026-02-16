<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Pages;

use App\Filament\Dashboard\Resources\OrganismResource;
use App\Models\Organism;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditOrganism extends EditRecord
{
    protected static string $resource = OrganismResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    /**
     * Merge current organism into target organism
     */
    public function mergeOrganism(int $targetId): void
    {
        $this->executeMerge($targetId);
    }

    /**
     * Execute the merge operation
     */
    protected function executeMerge(int $targetOrganismId): void
    {
        $currentOrganism = $this->record;
        $targetOrganism = Organism::findOrFail($targetOrganismId);

        if ($currentOrganism->id === $targetOrganismId) {
            Notification::make()
                ->title('Cannot merge organism into itself')
                ->danger()
                ->send();

            return;
        }

        DB::beginTransaction();

        try {
            // Get all molecule_organism records for the current organism
            $currentRelations = DB::table('molecule_organism')
                ->where('organism_id', $currentOrganism->id)
                ->get();

            $transferred = 0;
            $skipped = 0;

            foreach ($currentRelations as $relation) {
                // Check if this exact combination already exists for the target organism
                $exists = DB::table('molecule_organism')
                    ->where('organism_id', $targetOrganismId)
                    ->where('molecule_id', $relation->molecule_id)
                    ->where(function ($query) use ($relation) {
                        $query->where('sample_location_id', $relation->sample_location_id)
                            ->orWhere(function ($q) use ($relation) {
                                $q->whereNull('sample_location_id')
                                    ->where(DB::raw('1'), $relation->sample_location_id === null ? 1 : 0);
                            });
                    })
                    ->where(function ($query) use ($relation) {
                        $query->where('geo_location_id', $relation->geo_location_id)
                            ->orWhere(function ($q) use ($relation) {
                                $q->whereNull('geo_location_id')
                                    ->where(DB::raw('1'), $relation->geo_location_id === null ? 1 : 0);
                            });
                    })
                    ->where(function ($query) use ($relation) {
                        $query->where('ecosystem_id', $relation->ecosystem_id)
                            ->orWhere(function ($q) use ($relation) {
                                $q->whereNull('ecosystem_id')
                                    ->where(DB::raw('1'), $relation->ecosystem_id === null ? 1 : 0);
                            });
                    })
                    ->exists();

                if (! $exists) {
                    // Update the record to point to the target organism
                    DB::table('molecule_organism')
                        ->where('id', $relation->id)
                        ->update(['organism_id' => $targetOrganismId]);
                    $transferred++;
                } else {
                    // Delete the duplicate record from current organism
                    DB::table('molecule_organism')
                        ->where('id', $relation->id)
                        ->delete();
                    $skipped++;
                }
            }

            // Update molecule counts for both organisms
            $currentOrganism->molecule_count = DB::table('molecule_organism')
                ->where('organism_id', $currentOrganism->id)
                ->distinct('molecule_id')
                ->count('molecule_id');
            $currentOrganism->save();

            $targetOrganism->molecule_count = DB::table('molecule_organism')
                ->where('organism_id', $targetOrganismId)
                ->distinct('molecule_id')
                ->count('molecule_id');
            $targetOrganism->save();

            DB::commit();

            Notification::make()
                ->title('Organisms merged successfully')
                ->body("Transferred {$transferred} molecule relations to \"{$targetOrganism->name}\". {$skipped} duplicates were skipped.")
                ->success()
                ->send();

            // Redirect to the target organism's edit page
            $this->redirect(OrganismResource::getUrl('edit', ['record' => $targetOrganism]));

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Merge failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
