<?php

namespace App\Filament\Dashboard\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Dashboard\Resources\CitationResource\Pages\ListCitations;
use App\Filament\Dashboard\Resources\CitationResource\Pages\CreateCitation;
use App\Filament\Dashboard\Resources\CitationResource\Pages\EditCitation;
use App\Filament\Dashboard\Resources\CitationResource\Pages;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\CollectionRelationManager;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\MoleculeRelationManager;
use App\Models\Citation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CitationResource extends Resource
{
    protected static string | \UnitEnum | null $navigationGroup = 'Data';

    protected static ?string $model = Citation::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(Citation::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->wrap()
                    ->description(fn (Citation $citation): string => $citation->authors.' ~ '.$citation->doi)
                    ->searchable(),
                TextColumn::make('doi')->wrap()
                    ->label('DOI')
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
            CollectionRelationManager::class,
            MoleculeRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCitations::route('/'),
            'create' => CreateCitation::route('/create'),
            'edit' => EditCitation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::flexible('stats.citations', [172800, 259200], function () {
            return DB::table('citations')->selectRaw('count(*)')->get()[0]->count;
        });
    }
}
