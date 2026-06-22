<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\OrganismResource\Pages;
use App\Filament\Dashboard\Resources\OrganismResource\Pages\CreateOrganism;
use App\Filament\Dashboard\Resources\OrganismResource\Pages\EditOrganism;
use App\Filament\Dashboard\Resources\OrganismResource\Pages\ListOrganisms;
use App\Filament\Dashboard\Resources\OrganismResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\OrganismResource\RelationManagers\SampleLocationsRelationManager;
use App\Filament\Dashboard\Resources\OrganismResource\Widgets\OrganismStats;
use App\Forms\Components\OrganismsTable;
use App\Livewire\OrganismTaxonomyPanel;
use App\Models\Organism;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class OrganismResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $model = Organism::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema(Organism::getForm()),
                                Section::make('Taxonomic classification')
                                    ->description('Verified lineage and database links from Global Names (useful for interpreting metabolite origin).')
                                    ->schema([
                                        Livewire::make(OrganismTaxonomyPanel::class)
                                            ->key(fn (?Organism $record): string => 'organism-taxonomy-'.($record === null ? 'new' : $record->id))
                                            ->data(fn (?Organism $record): array => [
                                                'organismId' => $record === null ? 0 : $record->id,
                                            ]),
                                    ])
                                    ->hidden(fn (?string $operation): bool => $operation === 'create'),
                            ])
                            ->columnSpan(1),
                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema([
                                        OrganismsTable::make('Custom Table'),
                                        // \Livewire\Livewire::mount('similar-organisms', ['organismId' => function ($get) {
                                        //     return $get('name');
                                        // }]),
                                    ]),
                            ])
                            ->hidden(function ($operation) {
                                return $operation === 'create';
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(2),  // Defines the number of columns in the grid
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (Organism $record): ?string => $record->iri ? urldecode($record->iri) : null),
                TextColumn::make('rank')->wrap()
                    ->searchable(),
                TextColumn::make('molecule_count')
                    ->label('Molecules')
                    ->sortable(),
                TextColumn::make('taxonomy.biological_group')
                    ->label('Group')
                    ->toggleable(),
                TextColumn::make('taxonomy_fetched_at')
                    ->label('Taxonomy updated')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->recordActions([
                Action::make('iri')
                    ->label('IRI')
                    ->url(fn (Organism $record) => $record->iri ? urldecode($record->iri) : null, true)
                    ->color('info')
                    ->icon('heroicon-o-link')
                    ->iconButton(),
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(
                fn (Organism $record): string => self::getUrl('view', ['record' => $record]),
            );
    }

    public static function getRelations(): array
    {
        $arr = [
            MoleculesRelationManager::class,
            SampleLocationsRelationManager::class,
            AuditsRelationManager::class,
        ];

        return $arr;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganisms::route('/'),
            'create' => CreateOrganism::route('/create'),
            'edit' => EditOrganism::route('/{record}/edit'),
            'view' => Pages\ViewOrganism::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            OrganismStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::flexible('stats.organisms', [172800, 259200], function () {
            return DB::table('organisms')->selectRaw('count(*)')->get()[0]->count;
        });
    }
}
