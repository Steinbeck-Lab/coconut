<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\CitationResource\Pages;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\CollectionRelationManager;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\MoleculeRelationManager;
use App\Models\Citation;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CitationResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?string $model = Citation::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(Citation::getForm());
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
            CollectionRelationManager::class,
            MoleculeRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCitations::route('/'),
            'create' => Pages\CreateCitation::route('/create'),
            'edit' => Pages\EditCitation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::get('stats.citations');
    }
}
