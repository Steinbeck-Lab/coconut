<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\RelationManagers;

use App\Models\Molecule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class MoleculesRelationManager extends RelationManager
{
    protected static string $relationship = 'molecules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
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
                        return env('CM_API', 'https://api.cheminf.studio/latest/').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('id')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('identifier')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')->searchable()
                    ->formatStateUsing(
                        fn (Molecule $molecule): HtmlString => new HtmlString("<strong>ID:</strong> {$molecule->id}<br><strong>Identifier:</strong> {$molecule->identifier}<br><strong>Name:</strong> {$molecule->name}")
                    )
                    ->description(fn (Molecule $molecule): string => $molecule->standard_inchi)
                    ->wrap(),
                Tables\Columns\TextColumn::make('synonyms')
                    ->searchable()
                    ->wrap()
                    ->lineClamp(6)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('properties.exact_molecular_weight')
                    ->label('Mol.Wt')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('properties.np_likeness')
                    ->label('NP Likeness')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DissociateAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
