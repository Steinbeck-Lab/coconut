<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\Pages;

use App\Filament\Dashboard\Resources\MoleculeResource;
use App\Models\Molecule;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMolecules extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = MoleculeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('openLivewireModal')
                ->label('Structure Search')
                ->modalHeading('Structure Search')
                ->modalWidth('7xl')
                ->modalContent(view('livewire.molecule-filter'))
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'active' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('active', true))
                ->favorite()
                ->badge(Molecule::query()->where('active', true)->count())
                ->preserveAll()
                ->default(),
            'revoked' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where('active', false))
                ->favorite()
                ->badge(Molecule::query()->where('active', false)->count())
                ->preserveAll(),
            'drafts' => PresetView::make()
                ->modifyQueryUsing(fn ($query) => $query->where([['active', false], ['status', 'DRAFT']]))
                ->favorite()
                ->badge(Molecule::query()->where([['active', false], ['status', 'DRAFT']])->count())
                ->preserveAll(),
        ];
    }
}
