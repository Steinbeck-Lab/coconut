<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\SampleLocationResource\Pages;
use App\Filament\Dashboard\Resources\SampleLocationResource\RelationManagers\MoleculesRelationManager;
use App\Models\SampleLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class SampleLocationResource extends Resource
{
    protected static ?string $model = SampleLocation::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-s-viewfinder-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('iri')
                    ->maxLength(255),
                Forms\Components\Select::make('organism_id')
                    ->relationship('organisms', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('collection_ids')
                    ->maxLength(255),
                Forms\Components\TextInput::make('molecule_count')
                    ->numeric(),
                // Forms\Components\TextInput::make('slug')
                //     ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('iri')
                    ->searchable(),
                Tables\Columns\TextColumn::make('organism_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('collection_ids')
                    ->searchable(),
                Tables\Columns\TextColumn::make('molecule_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            MoleculesRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSampleLocations::route('/'),
            'create' => Pages\CreateSampleLocation::route('/create'),
            'view' => Pages\ViewSampleLocation::route('/{record}'),
            'edit' => Pages\EditSampleLocation::route('/{record}/edit'),
        ];
    }
}
