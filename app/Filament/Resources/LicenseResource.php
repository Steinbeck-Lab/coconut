<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseResource\Pages\CreateLicense;
use App\Filament\Resources\LicenseResource\Pages\EditLicense;
use App\Filament\Resources\LicenseResource\Pages\ListLicenses;
use App\Filament\Resources\LicenseResource\RelationManagers\CollectionsRelationManager;
use App\Models\License;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $model = License::class;

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title'),
                TextInput::make('spdx_id'),
                TextInput::make('url'),
                Textarea::make('description'),
                Textarea::make('body'),
                Textarea::make('category'),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->wrap()
                    ->searchable(),
                TextColumn::make('spdx_id')
                    ->searchable(),
                TextColumn::make('category')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category'),
            ])
            ->recordActions([
                EditAction::make(),
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
            CollectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
            'create' => CreateLicense::route('/create'),
            'edit' => EditLicense::route('/{record}/edit'),
        ];
    }
}
