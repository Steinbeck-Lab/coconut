<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\Pages;

use App\Filament\Dashboard\Resources\OrganismResource;
use App\Models\Organism;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganisms extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = OrganismResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'organisms' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('molecule_count', '>', 0))
                ->favorite()
                ->badge((string) Organism::query()->where('molecule_count', '>', 0)->count())
                ->preserveAll()
                ->default(),
            'inactive entries' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('molecule_count', '<=', 0))
                ->favorite()
                ->badge((string) Organism::query()->where('molecule_count', '<=', 0)->count())
                ->preserveAll(),
        ];
    }
}
