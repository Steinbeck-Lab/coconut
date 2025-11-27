<?php

namespace App\Models;

use Filament\Forms;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Organism extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'iri',
        'rank',
        'molecule_count',
        'slug',
    ];

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($organism) {
            if ($organism->name && ! $organism->slug) {
                $organism->slug = \Illuminate\Support\Str::slug($organism->name);
            }
        });
    }

    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class)->distinct('molecule_id')->orderBy('molecule_id')->withTimestamps();
    }

    public function moleculeRelations(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class)
            ->withPivot([
                'sample_location_id',
                'geo_location_id',
                'ecosystem_id',
                'collection_ids',
                'citation_ids',
                'notes',
            ])
            ->withTimestamps();
    }

    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    public function geoLocations(): BelongsToMany
    {
        return $this->belongsToMany(GeoLocation::class, 'molecule_organism', 'organism_id', 'geo_location_id')
            ->withTimestamps()
            ->distinct('geo_location_id')
            ->orderBy('geo_location_id');
    }

    public function ecosystems(): BelongsToMany
    {
        return $this->belongsToMany(Ecosystem::class, 'molecule_organism', 'organism_id', 'ecosystem_id')
            ->withTimestamps()
            ->distinct('ecosystem_id')
            ->orderBy('ecosystem_id');
    }

    public function sampleLocations(): BelongsToMany
    {
        return $this->belongsToMany(SampleLocation::class, 'molecule_organism', 'organism_id', 'sample_location_id')
            ->withTimestamps()
            ->distinct('sample_location_id')
            ->orderBy('sample_location_id');
    }

    public function getIriAttribute($value)
    {
        return urldecode($value);
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Organism Name')
                ->placeholder('e.g., Homo sapiens, Escherichia coli')
                ->required()
                ->unique(Organism::class, 'name', ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Enter the scientific name of the organism (genus and species)')
                ->hintActions([
                    \Filament\Actions\Action::make('searchGoogle')
                        ->label('Google')
                        ->icon('heroicon-m-globe-alt')
                        ->color('gray')
                        ->size('xs')
                        ->url(fn ($state) => $state ? 'https://www.google.com/search?q='.urlencode($state) : null, shouldOpenInNewTab: true)
                        ->visible(fn ($state) => filled($state)),
                    \Filament\Actions\Action::make('searchScholar')
                        ->label('Scholar')
                        ->icon('heroicon-m-academic-cap')
                        ->color('gray')
                        ->size('xs')
                        ->url(fn ($state) => $state ? 'https://scholar.google.com/scholar?q='.urlencode($state) : null, shouldOpenInNewTab: true)
                        ->visible(fn ($state) => filled($state)),
                ])
                ->live(onBlur: true)
                ->suffixAction(
                    fn (?string $state): \Filament\Actions\Action => \Filament\Actions\Action::make('lookupOrganism')
                        ->icon('heroicon-m-magnifying-glass')
                        ->label('Search')
                        ->tooltip('Search taxonomic databases for this organism')
                        ->form(function () use ($state) {
                            $name = trim($state ?? '');
                            if (empty($name)) {
                                return [
                                    Forms\Components\Placeholder::make('empty')
                                        ->content('Please enter an organism name first')
                                        ->columnSpanFull(),
                                ];
                            }

                            $results = self::searchAllSources($name);

                            if (empty($results)) {
                                return [
                                    Forms\Components\Placeholder::make('no_results')
                                        ->content("No taxonomic data found for: {$name}")
                                        ->columnSpanFull(),
                                ];
                            }

                            return [
                                Forms\Components\Radio::make('selected_result')
                                    ->label('Select a result')
                                    ->options(collect($results)->mapWithKeys(function ($result, $index) {
                                        $label = "{$result['name']} ({$result['rank']})";

                                        return [$index => $label];
                                    })->toArray())
                                    ->descriptions(collect($results)->mapWithKeys(function ($result, $index) {
                                        $desc = $result['source'];
                                        if ($result['iri']) {
                                            $desc .= ' • '.\Illuminate\Support\Str::limit($result['iri'], 50);
                                        }

                                        return [$index => $desc];
                                    })->toArray())
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Hidden::make('results_data')
                                    ->default(json_encode($results)),
                            ];
                        })
                        ->modalHeading('Taxonomy Search Results')
                        ->modalDescription(fn () => 'Results for: '.trim($state ?? ''))
                        ->modalSubmitActionLabel('Use Selected')
                        ->modalWidth('lg')
                        ->action(function (array $data, \Filament\Schemas\Components\Utilities\Set $set) {
                            if (! isset($data['selected_result']) || ! isset($data['results_data'])) {
                                return;
                            }

                            $results = json_decode($data['results_data'], true);
                            $selected = $results[$data['selected_result']] ?? null;

                            if ($selected) {
                                $set('iri', $selected['iri']);
                                $set('rank', $selected['rank']);

                                \Filament\Notifications\Notification::make()
                                    ->title('Organism data applied!')
                                    ->body("Applied: {$selected['name']} ({$selected['rank']}) from {$selected['source']}")
                                    ->success()
                                    ->send();
                            }
                        })
                )
                ->columnSpanFull(),

            Forms\Components\ToggleButtons::make('rank')
                ->label('Taxonomic Rank')
                ->options([
                    'domain' => 'Domain',
                    'kingdom' => 'Kingdom',
                    'phylum' => 'Phylum',
                    'class' => 'Class',
                    'order' => 'Order',
                    'family' => 'Family',
                    'genus' => 'Genus',
                    'species' => 'Species',
                    'subspecies' => 'Subspecies',
                    'variety' => 'Variety',
                    'strain' => 'Strain',
                ])
                ->default('species')
                ->inline()
                ->helperText('Select the taxonomic classification level of this organism')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('iri')
                ->label('IRI (Internationalized Resource Identifier)')
                ->placeholder('https://example.org/organisms/...')
                ->maxLength(255)
                ->url()
                ->helperText('Optional: Enter the ontology IRI/URI (e.g., from NCBI Taxonomy, OLS)')
                ->suffixIcon('heroicon-m-link')
                ->columnSpanFull(),
        ];
    }

    /**
     * Search all sources and return all results
     */
    public static function searchAllSources($name)
    {
        $results = [];

        // Normalize name
        $name = ucfirst(strtolower(trim($name)));
        $genus = explode(' ', $name)[0];

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.ebi.ac.uk/ols4/api/v2/',
        ]);

        // Search OLS/NCBI Taxonomy
        try {
            $olsResults = self::searchOLS($client, $name);
            $results = array_merge($results, $olsResults);

            // Also search by genus if different from full name
            if ($genus !== $name) {
                $genusResults = self::searchOLS($client, $genus);
                $results = array_merge($results, $genusResults);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OLS search error: '.$e->getMessage());
        }

        // Search Global Names Finder
        try {
            $gnfResults = self::searchGNF($name);
            $results = array_merge($results, $gnfResults);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GNF search error: '.$e->getMessage());
        }

        // Remove duplicates based on IRI
        $seen = [];
        $uniqueResults = [];
        foreach ($results as $result) {
            $key = $result['iri'] ?? $result['name'];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueResults[] = $result;
            }
        }

        return $uniqueResults;
    }

    /**
     * Search OLS/NCBI Taxonomy and return all matches
     */
    protected static function searchOLS($client, $name)
    {
        $results = [];

        try {
            // Try exact match first
            $response = $client->get('entities', [
                'query' => [
                    'search' => $name,
                    'ontologyId' => 'ncbitaxon',
                    'exactMatch' => true,
                    'type' => 'class',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $results = array_merge($results, self::parseOLSResults($data, 'Exact match'));

            // Also try non-exact match for more results
            $response = $client->get('entities', [
                'query' => [
                    'search' => $name,
                    'ontologyId' => 'ncbitaxon',
                    'exactMatch' => false,
                    'type' => 'class',
                    'size' => 5,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $results = array_merge($results, self::parseOLSResults($data, 'Similar match'));

        } catch (\Exception $e) {
            // Silent fail
        }

        return $results;
    }

    /**
     * Parse OLS API results
     */
    protected static function parseOLSResults($data, $matchType)
    {
        $results = [];
        $rankMap = [
            'http://purl.obolibrary.org/obo/NCBITaxon_species' => 'species',
            'http://purl.obolibrary.org/obo/NCBITaxon_genus' => 'genus',
            'http://purl.obolibrary.org/obo/NCBITaxon_family' => 'family',
            'http://purl.obolibrary.org/obo/NCBITaxon_order' => 'order',
            'http://purl.obolibrary.org/obo/NCBITaxon_class' => 'class',
            'http://purl.obolibrary.org/obo/NCBITaxon_phylum' => 'phylum',
            'http://purl.obolibrary.org/obo/NCBITaxon_kingdom' => 'kingdom',
            'http://purl.obolibrary.org/obo/NCBITaxon_domain' => 'domain',
            'http://purl.obolibrary.org/obo/NCBITaxon_subspecies' => 'subspecies',
            'http://purl.obolibrary.org/obo/NCBITaxon_varietas' => 'variety',
            'http://purl.obolibrary.org/obo/NCBITaxon_strain' => 'strain',
        ];

        if (isset($data['elements']) && count($data['elements']) > 0) {
            foreach ($data['elements'] as $element) {
                if (isset($element['iri']) && $element['isObsolete'] === false) {
                    $rankIri = $element['http://purl.obolibrary.org/obo/ncbitaxon#has_rank'] ?? null;
                    $rank = $rankMap[$rankIri] ?? 'unknown';
                    $label = $element['label'][0] ?? 'Unknown';

                    $results[] = [
                        'name' => $label,
                        'iri' => $element['iri'],
                        'rank' => $rank,
                        'source' => 'NCBI Taxonomy (OLS)',
                        'match_type' => $matchType,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Search Global Names Finder and return all matches
     */
    protected static function searchGNF($name)
    {
        $results = [];

        try {
            $client = new \GuzzleHttp\Client;
            $response = $client->post('https://finder.globalnames.org/api/v1/find', [
                'json' => [
                    'text' => $name,
                    'bytesOffset' => false,
                    'returnContent' => false,
                    'uniqueNames' => true,
                    'ambiguousNames' => true,
                    'noBayes' => false,
                    'oddsDetails' => false,
                    'language' => 'eng',
                    'wordsAround' => 0,
                    'verification' => true,
                    'allMatches' => true,
                ],
            ]);

            $responseBody = json_decode($response->getBody(), true);

            if (isset($responseBody['names']) && count($responseBody['names']) > 0) {
                foreach ($responseBody['names'] as $r_name) {
                    $matchType = $r_name['verification']['matchType'] ?? null;
                    $foundName = $r_name['name'] ?? $name;

                    // Handle verified matches (Exact, Fuzzy, PartialExact, PartialFuzzy)
                    if (in_array($matchType, ['Exact', 'Fuzzy', 'PartialExact', 'PartialFuzzy'])) {
                        // Get best result
                        $bestResult = $r_name['verification']['bestResult'] ?? null;
                        if ($bestResult) {
                            $iri = $bestResult['outlink'] ?? null;
                            $dataSource = $bestResult['dataSourceTitleShort'] ?? 'Unknown';
                            $matchedName = $bestResult['matchedName'] ?? $foundName;
                            $ranks = $bestResult['classificationRanks'] ?? null;

                            $rank = 'unknown';
                            if ($ranks) {
                                $ranks = rtrim($ranks, '|');
                                $ranksArray = explode('|', $ranks);
                                $rank = strtolower(end($ranksArray));
                            }

                            $results[] = [
                                'name' => $matchedName,
                                'iri' => $iri,
                                'rank' => $rank,
                                'source' => "Global Names ({$dataSource})",
                                'match_type' => $matchType,
                            ];
                        }

                        // Also include other results if available
                        if (isset($r_name['verification']['results'])) {
                            foreach (array_slice($r_name['verification']['results'], 0, 3) as $result) {
                                $iri = $result['outlink'] ?? null;
                                $dataSource = $result['dataSourceTitleShort'] ?? 'Unknown';
                                $matchedName = $result['matchedName'] ?? $name;
                                $ranks = $result['classificationRanks'] ?? null;

                                $rank = 'unknown';
                                if ($ranks) {
                                    $ranks = rtrim($ranks, '|');
                                    $ranksArray = explode('|', $ranks);
                                    $rank = strtolower(end($ranksArray));
                                }

                                $results[] = [
                                    'name' => $matchedName,
                                    'iri' => $iri,
                                    'rank' => $rank,
                                    'source' => "Global Names ({$dataSource})",
                                    'match_type' => $matchType,
                                ];
                            }
                        }
                    }
                    // Handle NoMatch - GNF recognized the name but couldn't verify it
                    elseif ($matchType === 'NoMatch' && $foundName) {
                        $results[] = [
                            'name' => $foundName,
                            'iri' => null,
                            'rank' => 'unknown',
                            'source' => 'Global Names (Unverified)',
                            'match_type' => 'Name recognized',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return $results;
    }
}
