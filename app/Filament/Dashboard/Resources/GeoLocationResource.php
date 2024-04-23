<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\GeoLocationResource\Pages;
use App\Filament\Dashboard\Resources\GeoLocationResource\RelationManagers;
use App\Models\GeoLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;

class GeoLocationResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 5;
    
    protected static ?string $model = GeoLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                // Fieldset::make('Molecule')
                //     ->relationship('molecules', 'identifier')
                //     ->schema([
                //         TextInput::make('identifier'),
                //         TextInput::make('locations'),
                //     ])
                // TextInput::make('molecule_id')
                //     ->label('Molecule')
                //     ->relationship('molecule')
                //     ->placeholder('Enter the molecule Identifier')
                //     ->required(),
                // TextInput::make('locations')
                //     ->label('Locations')
                //     ->relationship('molecule')
                //     ->placeholder('soil, water, etc.')
                //     ->helperText('Enter where in this Geo-Location these molecules can be found')
                //     ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeoLocations::route('/'),
            'create' => Pages\CreateGeoLocation::route('/create'),
            'edit' => Pages\EditGeoLocation::route('/{record}/edit'),
        ];
    }
}
