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
use App\Models\Organism;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Log;
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
                Section::make('Organism Information')
                    ->description('Enter the organism details below')
                    ->schema(Organism::getForm()),

                Section::make('Similar Organisms')
                    ->description('View similar organisms in the database')
                    ->schema([
                        OrganismsTable::make('Custom Table'),
                    ])
                    ->hidden(function ($operation) {
                        return $operation === 'create';
                    })
                    ->collapsible(),
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
            // Auto-generate slug if not exists
            if (! $organism->slug) {
                $organism->slug = \Illuminate\Support\Str::slug($name);
            }
            $organism->save();
        } else {
            self::error("Organism not found in the database: $name");
        }
    }

    protected static function getOLSIRI($name, $rank)
    {
        $client = new Client([
            'base_uri' => 'https://www.ebi.ac.uk/ols4/api/v2/',
        ]);

        try {
            $response = $client->get('entities', [
                'query' => [
                    'search' => $name,
                    'ontologyId' => 'ncbitaxon',
                    'exactMatch' => true,
                    'type' => 'class',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['elements']) && count($data['elements']) > 0) {

                $element = $data['elements'][0];
                if (isset($element['iri'], $element['ontologyId']) && $element['isObsolete'] === false) {
                    if ($rank && $rank == 'species') {
                        if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_species') {
                            return urlencode($element['iri']);
                        }
                    } elseif ($rank && $rank == 'genus') {
                        if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_genus') {
                            return urlencode($element['iri']);
                        }
                    } elseif ($rank && $rank == 'family') {
                        if (isset($element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank']) && $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] == 'http://purl.obolibrary.org/obo/NCBITaxon_family') {
                            return urlencode($element['iri']);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("Error fetching IRI for $name: ".$e->getMessage());
        }

        return null;
    }
}
