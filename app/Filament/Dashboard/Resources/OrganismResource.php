<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\OrganismResource\Pages;
use App\Filament\Dashboard\Resources\OrganismResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\OrganismResource\RelationManagers\SampleLocationsRelationManager;
use App\Filament\Dashboard\Resources\OrganismResource\Widgets\OrganismStats;
use App\Forms\Components\OrganismsTable;
use App\Models\Organism;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use GuzzleHttp\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Log;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class OrganismResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $model = Organism::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema(Organism::getForm()),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rank')->wrap()
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
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->actions([
                Tables\Actions\Action::make('iri')
                    ->label('IRI')
                    ->url(fn (Organism $record) => $record->iri ? urldecode($record->iri) : null, true)
                    ->color('info')
                    ->icon('heroicon-o-link'),
                // Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListOrganisms::route('/'),
            'create' => Pages\CreateOrganism::route('/create'),
            'edit' => Pages\EditOrganism::route('/{record}/edit'),
            // 'view' => Pages\ViewOrganism::route('/{record}'),
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
        return Cache::get('stats.organisms');
    }

    protected static function getGNFMatches($name, $organism)
    {
        $data = [
            'text' => $name,
            'bytesOffset' => false,
            'returnContent' => false,
            'uniqueNames' => true,
            'ambiguousNames' => true,
            'noBayes' => false,
            'oddsDetails' => false,
            'language' => 'eng',
            'wordsAround' => 2,
            'verification' => true,
            'allMatches' => true,
        ];

        $client = new Client;
        $url = 'https://finder.globalnames.org/api/v1/find';

        $response = $client->post($url, [
            'json' => $data,
        ]);

        $responseBody = json_decode($response->getBody(), true);

        return $responseBody;

        // if (isset($responseBody['names']) && count($responseBody['names']) > 0) {
        //     $r_name = $responseBody['names'][0];
        //     $matchType = $r_name['verification']['matchType'];
        //     if ($matchType == 'Exact' || $matchType == 'Fuzzy') {
        //         $iri = $r_name['verification']['bestResult']['outlink'] ?? $r_name['verification']['bestResult']['dataSourceTitleShort'];
        //         $ranks = $r_name['verification']['bestResult']['classificationRanks'] ?? null;
        //         $ranks = rtrim($ranks, '|');
        //         $ranks = explode('|', $ranks);
        //         $rank = end($ranks);
        //         if ($matchType == 'Fuzzy') {
        //             $rank = $rank . ' (fuzzy)';
        //         }
        //         return [$name, $iri, $organism, $rank];
        //     } elseif ($matchType == 'PartialFuzzy' || $matchType == 'PartialExact') {
        //         $iri = $r_name['verification']['bestResult']['dataSourceTitleShort'];
        //         if (isset($r_name['verification']['bestResult']['classificationRanks'])) {
        //             $ranks = rtrim($r_name['verification']['bestResult']['classificationRanks'], '|') ?? null;
        //             $paths = rtrim($r_name['verification']['bestResult']['classificationPath'], '|') ?? null;
        //             $ids = rtrim($r_name['verification']['bestResult']['classificationIds'], '|') ?? null;
        //             $ranks = explode('|', $ranks);
        //             $ranksLength = count($ranks);
        //             if ($ranksLength > 0) {
        //                 $parentRank = $ranks[$ranksLength - 2];
        //                 $parentName = $paths[$ranksLength - 2];
        //                 $parentId = $ids[$ranksLength - 2];

        //                 return [$name, $iri, $organism, $parentRank];
        //             }
        //         }
        //     }
        // } else {
        //     Self::error("Could not map: $name");
        // }
    }

    protected static function updateOrganismModel($name, $iri, $organism = null, $rank = null)
    {
        if (! $organism) {
            $organism = Organism::where('name', $name)->first();
        }

        if ($organism) {
            $organism->name = $name;
            $organism->iri = $iri;
            $organism->rank = $rank;
            $organism->save();
        } else {
            self::error("Organism not found in the database: $name");
        }
    }

    protected static function getOLSIRI($name, $rank)
    {
        $client = new Client([
            'base_uri' => 'https://www.ebi.ac.uk/ols4/api/',
        ]);

        try {
            $response = $client->get('search', [
                'query' => [
                    'q' => $name,
                    'ontology' => ['ncbitaxon', 'efo', 'obi', 'uberon', 'taxrank'],
                    'exact' => false,
                    'obsoletes' => false,
                    'format' => 'json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return $data;
            // var_dump($data);

            // if (isset($data['elements']) && count($data['elements']) > 0) {

            //     $element = $data['elements'][0];
            //     if (isset($element['iri'], $element['ontologyId']) && $element['isObsolete'] === 'false') {
            //         if ($rank && $rank == 'species') {
            //             if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_species') {
            //                 return urlencode($element['iri']);
            //             }
            //         } elseif ($rank && $rank == 'genus') {
            //             if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_genus') {
            //                 return urlencode($element['iri']);
            //             }
            //         } elseif ($rank && $rank == 'family') {
            //             if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_family') {
            //                 return urlencode($element['iri']);
            //             }
            //         }
            //     }
            // }
        } catch (\Exception $e) {
            // Self::error("Error fetching IRI for $name: " . $e->getMessage());
            Log::error("Error fetching IRI for $name: ".$e->getMessage());
        }

        return null;
    }
}
