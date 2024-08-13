<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Pages;

use App\Filament\Dashboard\Resources\OrganismResource;
use App\Models\Organism;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;

class ListOrganisms extends ListRecords
{
    use AdvancedTables;
    protected static string $resource = OrganismResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'organisms' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('molecule_count', '>', 0))
                ->favorite()
                ->badge(Organism::query()->where('molecule_count', '>', 0)->count())
                ->preserveAll()
                ->default(),
            'inactive entries' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('molecule_count', '<=', 0))
                ->favorite()
                ->badge(Organism::query()->where('molecule_count', '<=', 0)->count())
                ->preserveAll(),
        ];
    }
}
