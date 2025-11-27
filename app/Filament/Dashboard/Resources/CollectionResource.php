<?php

namespace App\Filament\Dashboard\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\ListCollections;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\CreateCollection;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\EditCollection;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\ViewCollection;
use App\Filament\Dashboard\Resources\CollectionResource\Pages;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\CitationsRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\EntriesRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\CollectionStats;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\EntriesOverview;
use App\Livewire\ShowJobStatus;
use App\Models\Collection;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 1;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    // Livewire::make(ShowJobStatus::class),
                    Grid::make()
                        ->schema([
                            Select::make('status')
                                ->options(getCollectionStatuses())
                                ->default(function (?Model $record) {
                                    return $record->status ?? 'DRAFT';
                                })
                                ->required()
                                ->hidden(function (?Model $record) {
                                    return auth()->user()->cannot('update', $record);
                                }),
                        ])->columns(4),
                    Section::make('Database details')
                        ->description('Provide details of the database and link to the resource.')
                        ->schema([
                            TextInput::make('title'),
                            Textarea::make('description'),
                            TextInput::make('url'),
                        ]),
                    Section::make('Meta data')
                        ->schema([
                            SpatieTagsInput::make('tags')
                                ->splitKeys(['Tab', ','])
                                ->type('collections'),
                            TextInput::make('identifier'),
                        ]),
                    Section::make()
                        ->schema([
                            FileUpload::make('image')
                                ->label('Display image')
                                ->image()
                                ->directory('collections')
                                ->visibility('public')
                                ->imageEditor()
                                ->imageEditorAspectRatios([
                                    '1:1',
                                ]),

                        ]),
                    Section::make('Distribution')
                        ->schema([
                            Select::make('license')
                                ->relationship('license', 'title')
                                ->preload()
                                ->searchable(),
                        ]),
                ]
            )->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('entries')
                    ->state(function (Model $record) {
                        return $record->total_entries.'/'.$record->failed_entries;
                    }),
                TextColumn::make('molecules_count')
                    ->label('Molecules')
                    ->sortable(),
                TextColumn::make('citations_count')
                    ->label('Citations')
                    ->sortable(),
                TextColumn::make('organisms_count')
                    ->label('Organisms')
                    ->sortable(),
                TextColumn::make('geo_count')
                    ->label('Geo Locations')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT' => 'info',
                        'REVIEW' => 'warning',
                        'EMBARGO' => 'warning',
                        'PUBLISHED' => 'success',
                        'REJECTED' => 'danger',
                    }),
            ])
            ->searchable(false)
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(100);
    }

    public static function getRelations(): array
    {
        $arr = [
            EntriesRelationManager::class,
            CitationsRelationManager::class,
            AuditsRelationManager::class,
            MoleculesRelationManager::class,
        ];

        return $arr;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'edit' => EditCollection::route('/{record}/edit'),
            'view' => ViewCollection::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CollectionStats::class,
            EntriesOverview::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::flexible('stats.collections', [172800, 259200], function () {
            return DB::table('collections')->selectRaw('count(*)')->get()[0]->count;
        });
    }
}
