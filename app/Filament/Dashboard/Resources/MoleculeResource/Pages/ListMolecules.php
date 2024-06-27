<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Pages;

use App\Filament\Dashboard\Resources\MoleculeResource;
use App\Models\Molecule;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMolecules extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = MoleculeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'active mols' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('active', true))
                ->favorite()
                ->badge(Molecule::query()->where('active', true)->count())
                ->preserveAll()
                ->default(),
            'deactive mols' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('active', false))
                ->favorite()
                ->badge(Molecule::query()->where('active', false)->count())
                ->preserveAll(),
        ];
    }
}
