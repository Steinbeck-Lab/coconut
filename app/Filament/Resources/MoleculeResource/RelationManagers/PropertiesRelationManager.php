<?php

namespace App\Filament\Resources\MoleculeResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('property')
            ->columns([
                Tables\Columns\TextColumn::make('molecular_formula'),
                Tables\Columns\TextColumn::make('np_likeness'),
                Tables\Columns\TextColumn::make('molecular_weight'),
                Tables\Columns\TextColumn::make('total_atom_count'),
                Tables\Columns\TextColumn::make('heavy_atom_count'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
