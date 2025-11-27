<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\GeoLocationResource\Pages\CreateGeoLocation;
use App\Filament\Dashboard\Resources\GeoLocationResource\Pages\EditGeoLocation;
use App\Filament\Dashboard\Resources\GeoLocationResource\Pages\ListGeoLocations;
use App\Filament\Dashboard\Resources\GeoLocationResource\Pages\ViewGeoLocation;
use App\Filament\Dashboard\Resources\GeoLocationResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\GeoLocationResource\Widgets\GeoLocationStats;
use App\Models\GeoLocation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class GeoLocationResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 5;

    protected static ?string $model = GeoLocation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(GeoLocation::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flag')
                    ->label('')
                    ->formatStateUsing(fn ($state) => $state ?: '🌍')
                    ->size('xl')
                    ->alignCenter()
                    ->width(60),
                TextColumn::make('name')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => implode(' • ', array_filter([
                        $record->county,
                        $record->country,
                    ])))
                    ->wrap()
                    ->weight('medium'),
                TextColumn::make('country_code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coordinates')
                    ->label('Coordinates')
                    ->formatStateUsing(fn ($record) => $record->latitude && $record->longitude
                            ? sprintf('%.4f°, %.4f°', $record->latitude, $record->longitude)
                            : '—'
                    )
                    ->icon('heroicon-m-map-pin')
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('molecules_count')
                    ->label('Molecules')
                    ->counts('molecules')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                //
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
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
            'index' => ListGeoLocations::route('/'),
            'create' => CreateGeoLocation::route('/create'),
            'edit' => EditGeoLocation::route('/{record}/edit'),
            'view' => ViewGeoLocation::route('/{record}'),
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
        return Cache::flexible('stats.geo_locations', [172800, 259200], function () {
            return DB::table('geo_locations')->selectRaw('count(*)')->get()[0]->count;
        });
    }
}
