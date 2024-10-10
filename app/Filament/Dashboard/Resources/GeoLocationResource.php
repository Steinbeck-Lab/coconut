<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\GeoLocationResource\Pages;
use App\Filament\Dashboard\Resources\GeoLocationResource\Widgets\GeoLocationStats;
use App\Models\GeoLocation;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class GeoLocationResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $model = GeoLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(GeoLocation::getForm());
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
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeoLocations::route('/'),
            'create' => Pages\CreateGeoLocation::route('/create'),
            'edit' => Pages\EditGeoLocation::route('/{record}/edit'),
            'view' => Pages\ViewGeoLocation::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            GeoLocationStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::get('stats.geo_locations');
    }
}
