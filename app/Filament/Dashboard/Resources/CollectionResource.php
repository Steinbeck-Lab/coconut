<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\CollectionResource\Pages;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\CitationsRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\EntriesRelationManager;
use App\Models\Collection;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                [
                    Section::make('Database details')
                        ->description('Provide details of the database and link to the resource.')
                        ->schema([
                            TextInput::make('title'),
                            TextArea::make('description'),
                            TextInput::make('url'),
                        ]),
                    Section::make('Meta data')
                        ->schema([
                            SpatieTagsInput::make('tags')
                                ->type('collections'),
                            TextInput::make('identifier'),
                        ]),
                    Section::make('Distribution')
                        ->schema([
                            Select::make('license')
                                ->relationship('license', 'title')
                                ->preload()
                                ->searchable(),
                            // ToggleButtons::make('status')
                            //     ->options([
                            //         'DRAFT' => 'Draft',
                            //         'REVIEW' => 'Review',
                            //         'EMBARGO' => 'Embargo',
                            //         'PUBLISHED' => 'Published',
                            //         'REJECTED' => 'Rejected',
                            //     ])->inline(),
                        ]),
                ]
            )->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->wrap(),
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
            EntriesRelationManager::class,
            CitationsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
