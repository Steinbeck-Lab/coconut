<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\CollectionResource\Pages\CreateCollection;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\EditCollection;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\ListCollections;
use App\Filament\Dashboard\Resources\CollectionResource\Pages\ViewCollection;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\CitationsRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\EntriesRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\CollectionStats;
use App\Filament\Dashboard\Resources\CollectionResource\Widgets\EntriesOverview;
use App\Livewire\ShowJobStatus;
use App\Models\Collection;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

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
                    Section::make('Database Details')
                        ->description('Provide details of the collection and link to the resource.')
                        ->schema([
                            TextInput::make('title')
                                ->label('Collection Title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter the name of your collection')
                                ->helperText('A descriptive title for your collection'),
                            Textarea::make('description')
                                ->label('Description')
                                ->required()
                                ->rows(4)
                                ->placeholder('Provide a detailed description of your collection')
                                ->helperText('Describe the contents, scope, and purpose of this collection'),
                            TextInput::make('url')
                                ->label('URL')
                                ->url()
                                ->placeholder('https://example.com/collection')
                                ->helperText('Link to the external database or resource (optional)')
                                ->suffixAction(
                                    fn (?string $state): Action => Action::make('visit')
                                        ->icon('heroicon-o-arrow-top-right-on-square')
                                        ->url($state, shouldOpenInNewTab: true)
                                ),
                        ]),
                    Section::make('Metadata')
                        ->description('Additional information to help categorize and identify your collection.')
                        ->schema([
                            SpatieTagsInput::make('tags')
                                ->label('Tags')
                                ->placeholder('Add tags (press Tab or comma to add)')
                                ->splitKeys(['Tab', ','])
                                ->type('collections')
                                ->helperText('Add keywords or categories to help users discover your collection'),
                            TextInput::make('identifier')
                                ->label('Identifier')
                                ->maxLength(255)
                                ->placeholder('e.g., accession number or unique ID')
                                ->helperText('Unique identifier for this collection (optional)'),
                        ]),
                    Section::make('Display Image')
                        ->description('Upload an image to visually represent your collection.')
                        ->schema([
                            FileUpload::make('image')
                                ->label('Collection Image')
                                ->image()
                                ->directory('collections')
                                ->visibility('public')
                                ->imageEditor()
                                ->imageEditorAspectRatios([
                                    '1:1',
                                ])
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                ->maxSize(5120)
                                ->helperText('Upload a square image (1:1 ratio, max 5MB). Accepted formats: PNG, JPEG, WebP'),

                        ]),
                    Section::make('Distribution')
                        ->description('Specify the license under which this collection is distributed.')
                        ->schema([
                            Select::make('license')
                                ->label('License')
                                ->relationship('license', 'title')
                                ->preload()
                                ->searchable()
                                ->placeholder('Select a license')
                                ->helperText('Choose the license that governs the use of this collection'),
                        ]),
                ]
            )->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Database Details')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Collection Title'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('url')
                            ->label('URL')
                            ->url(fn ($record) => $record->url)
                            ->openUrlInNewTab()
                            ->placeholder('No URL provided'),
                    ])
                    ->columns(2),
                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('tags.*.name')
                            ->label('Tags')
                            ->badge()
                            ->placeholder('No tags'),
                        TextEntry::make('identifier')
                            ->label('Identifier')
                            ->placeholder('No identifier'),
                    ])
                    ->columns(2),
                Section::make('Display Image')
                    ->schema([
                        ImageEntry::make('image')
                            ->label('Collection Image')
                            ->visibility('public')
                            ->size(200)
                            ->state(fn ($record) => $record->image ? 'https://s3.uni-jena.de/coconut/' . $record->image : null),
                    ]),
                Section::make('Distribution')
                    ->schema([
                        TextEntry::make('license.title')
                            ->label('License')
                            ->placeholder('No license specified'),
                    ]),
            ]);
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
