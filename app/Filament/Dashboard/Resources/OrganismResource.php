<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\OrganismResource\Pages;
use App\Filament\Dashboard\Resources\OrganismResource\Widgets\OrganismStats;
use App\Models\Organism;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class OrganismResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $model = Organism::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ontology')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ontology')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
        // $record = static::getOwner();
        // dd(static::getOwner());
        // dd(static::$model::molecules()->get());
        $arr = [
            MoleculesRelationManager::class,
        ];

        return $arr;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganisms::route('/'),
            'create' => Pages\CreateOrganism::route('/create'),
            'edit' => Pages\EditOrganism::route('/{record}/edit'),
            'view' => Pages\ViewOrganism::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            OrganismStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::get('stats.organisms');
    }
}
