<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\RelationManagers;

use App\Filament\Dashboard\Resources\MoleculeResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MoleculesRelationManager extends RelationManager
{
    protected static string $relationship = 'molecules';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('canonical_smiles')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identifier')
            ->columns([
                ImageColumn::make('structure')->square()
                    ->label('Structure')
                    ->state(function ($record) {
                        return config('services.cheminf.api_url').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                TextColumn::make('identifier')
                    ->label('Identifier')
                    ->url(fn ($record) => MoleculeResource::getUrl('view', ['record' => $record->id]), shouldOpenInNewTab: true)
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
