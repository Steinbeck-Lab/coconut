<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MoleculeResource\Pages;
use App\Filament\Resources\MoleculeResource\RelationManagers\CitationsRelationManager;
use App\Filament\Resources\MoleculeResource\RelationManagers\CollectionsRelationManager;
use App\Filament\Resources\MoleculeResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Resources\MoleculeResource\RelationManagers\PropertiesRelationManager;
use App\Models\Molecule;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class MoleculeResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?string $model = Molecule::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('identifier'),
                TextInput::make('iupac_name'),
                TextInput::make('standard_inchi'),
                TextInput::make('standard_inchi_key'),
                TextArea::make('iupac_name'),
                TextInput::make('canonical_smiles'),
                TextInput::make('murko_framework'),
                TextArea::make('synonyms'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identifier')
            ->columns([
                ImageColumn::make('structure')->square()
                    ->label('Structure')
                    ->state(function ($record) {
                        return env('CM_API', 'https://dev.api.naturalproducts.net/latest/').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=false&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('identifier')->searchable(),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            PropertiesRelationManager::class,
            CollectionsRelationManager::class,
            CitationsRelationManager::class,
            MoleculesRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMolecules::route('/'),
            'create' => Pages\CreateMolecule::route('/create'),
            'edit' => Pages\EditMolecule::route('/{record}/edit'),
        ];
    }
}
