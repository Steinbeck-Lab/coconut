<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Pages;

use App\Filament\Dashboard\Resources\CollectionResource;
use App\Models\Collection;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Components\PresetView;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollections extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getPresetViews(): array
    {
        return [
            'published' => PresetView::make()
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'PUBLISHED'))
                ->favorite()
                ->badge((string) Collection::query()->where('status', 'PUBLISHED')->count())
                ->preserveAll()
                ->default(),
            'draft' => PresetView::make()
                ->icon('heroicon-o-pencil-square')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'DRAFT'))
                ->favorite()
                ->badge((string) Collection::query()->where('status', 'DRAFT')->count())
                ->preserveAll(),
        ];
    }
}
