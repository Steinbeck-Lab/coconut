<?php

namespace App\Filament\Dashboard\Resources\GeoLocationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MoleculesRelationManager extends RelationManager
{
    protected static string $relationship = 'molecules';

    protected static ?string $title = 'Molecules';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identifier')
            ->columns([
                ImageColumn::make('structure')
                    ->square()
                    ->label('Structure')
                    ->state(function ($record) {
                        return config('services.cheminf.api_url').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                TextColumn::make('identifier')
                    ->label('Molecule Information')
                    ->searchable(['identifier', 'name', 'iupac_name'])
                    ->description(fn ($record) => implode(' • ', array_filter([
                        $record->name,
                        $record->iupac_name,
                    ])))
                    ->url(fn ($record) => route('compound', ['id' => $record->identifier]), shouldOpenInNewTab: true)
                    ->color('primary')
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No create/attach actions - read only
            ])
            ->recordActions([
                // No actions - read only, click to open in new tab
            ])
            ->bulkActions([
                // No bulk actions
            ]);
    }
}
