<?php

namespace App\Filament\Dashboard\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Dashboard\Resources\SampleLocationResource\Pages\ListSampleLocations;
use App\Filament\Dashboard\Resources\SampleLocationResource\Pages\CreateSampleLocation;
use App\Filament\Dashboard\Resources\SampleLocationResource\Pages\ViewSampleLocation;
use App\Filament\Dashboard\Resources\SampleLocationResource\Pages\EditSampleLocation;
use App\Filament\Dashboard\Resources\SampleLocationResource\Pages;
use App\Filament\Dashboard\Resources\SampleLocationResource\RelationManagers\MoleculesRelationManager;
use App\Models\SampleLocation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class SampleLocationResource extends Resource
{
    protected static ?string $model = SampleLocation::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | \UnitEnum | null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 6;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-s-viewfinder-circle';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('iri')
                    ->maxLength(255),
                Select::make('organism_id')
                    ->relationship('organisms', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('collection_ids')
                    ->maxLength(255),
                TextInput::make('molecule_count')
                    ->numeric(),
                // Forms\Components\TextInput::make('slug')
                //     ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('iri')
                    ->searchable(),
                TextColumn::make('organism_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('collection_ids')
                    ->searchable(),
                TextColumn::make('molecule_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListSampleLocations::route('/'),
            'create' => CreateSampleLocation::route('/create'),
            'view' => ViewSampleLocation::route('/{record}'),
            'edit' => EditSampleLocation::route('/{record}/edit'),
        ];
    }
}
