<?php

namespace App\Filament\Dashboard\Resources;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Dashboard\Resources\EntryResource\Pages\ListEntries;
use App\Filament\Dashboard\Resources\EntryResource\Pages\CreateEntry;
use App\Filament\Dashboard\Resources\EntryResource\Pages\ViewEntry;
use App\Filament\Dashboard\Resources\EntryResource\Pages\EditEntry;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Dashboard\Resources\EntryResource\Pages;
use App\Models\Entry;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EntryResource extends Resource
{
    protected static ?string $model = Entry::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntries::route('/'),
            'create' => CreateEntry::route('/create'),
            'view' => ViewEntry::route('/{record}'),
            'edit' => EditEntry::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('indentifier'),
            ]);
    }
}
