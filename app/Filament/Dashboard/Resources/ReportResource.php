<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Models\Report;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class ReportResource extends Resource
{
    protected static ?string $navigationGroup = 'Reporting';

    protected static ?string $model = Report::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        ToggleButtons::make('is_change')
                            ->label('')
                            ->live()
                            ->default(false)
                            ->options([
                                false => 'Report Synthetic Compound(s)',
                                true => 'Request Changes to Data',
                            ])
                            ->inline()
                            ->columnSpan(2),
                        Actions::make([
                            Action::make('approve')
                                ->form([
                                    Textarea::make('reason'),
                                ])
                                ->action(function (array $data, Report $record, Molecule $molecule, $set, $livewire): void {
                                    self::approveReport($data, $record, $molecule, $livewire);
                                    $set('status', 'approved');
                                }),
                            Action::make('reject')
                                ->color('danger')
                                ->form([
                                    Textarea::make('reason'),
                                ])
                                ->action(function (array $data, Report $record, $set): void {
                                    self::rejectReport($data, $record);
                                    $set('status', 'rejected');
                                }),
                        ])
                            ->hidden(function (Get $get, string $operation) {
                                return ! auth()->user()->roles()->exists() || $get('status') == 'rejected' || $get('status') == 'approved' || $operation != 'edit';
                            })
                            ->verticalAlignment(VerticalAlignment::End)
                            ->columnStart(4),
                    ])
                    ->columns(3),

                Select::make('report_type')
                    ->label('Choose')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select what you want to report. Ex: Molecule, Citation, Collection, Organism.')
                    ->live()
                    ->options(function () {
                        return getReportTypes();
                    })
                    ->hidden(function (string $operation) {
                        if ($operation == 'create') {
                            return false;
                        } else {
                            return true;
                        }
                    }),
                TextInput::make('title')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Title of the report. This is required.')
                    ->required(),
                Textarea::make('evidence')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Please provide Evidence/Comment to support your claims in this report. This will help our Curators in reviewing your report.')
                    ->label('Evidence/Comment')
                    ->hidden(function (Get $get) {
                        return $get('is_change');
                    }),
                Tabs::make('suggested_changes')
                    ->tabs([
                        Tabs\Tab::make('organisms_changes')
                            ->label('Organisms')
                            ->schema([
                                Repeater::make('organisms_changes')
                                    ->schema([
                                        Checkbox::make('approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->disabled(false),
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live()
                                            ->columnSpan(2),
                                        Select::make('organisms')
                                            ->label('Organism')
                                            ->searchable()
                                            ->searchDebounce(500)
                                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                                return Molecule::where('identifier', $get('../../mol_id_csv'))->get()[0]->organisms()->where('name', 'ilike', "%{$search}%")->limit(10)->pluck('organisms.name', 'organisms.id')->toArray() ?? [];
                                            })
                                            ->getOptionLabelUsing(fn ($value): ?string => Organism::find($value)?->name)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') == 'add';
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            })
                                            ->columnSpan(2),
                                        Grid::make('new_organism_details')
                                            ->schema(Organism::getForm())->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(7),

                            ]),
                        Tabs\Tab::make('geo_locations_changes')
                            ->label('Geo Locations')
                            ->schema([
                                Repeater::make('geo_locations_changes')
                                    ->schema([
                                        Checkbox::make('approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            }),
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live()
                                            ->columnSpan(2),
                                        Select::make('geo_locations')
                                            ->label('Geo Location')
                                            ->searchable()
                                            ->searchDebounce(500)
                                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                                return Molecule::where('identifier', $get('../../mol_id_csv'))->get()[0]->geo_locations()->where('name', 'ilike', "%{$search}%")->limit(10)->pluck('geo_locations.name', 'geo_locations.id')->toArray();
                                            })
                                            ->getOptionLabelUsing(fn ($value): ?string => GeoLocation::find($value)?->name)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') == 'add';
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            })
                                            ->columnSpan(2),
                                        Grid::make('new_geo_locations_details')
                                            ->schema(GeoLocation::getForm())->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(7),
                            ]),
                        Tabs\Tab::make('synonyms')
                            ->label('Synonyms')
                            ->schema([
                                Repeater::make('synonyms_changes')
                                    ->schema([
                                        Checkbox::make('approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            }),
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live()
                                            ->columnSpan(2),
                                        Select::make('synonyms')
                                            ->label('Synonym')
                                            ->searchable()
                                            ->searchDebounce(500)
                                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                                $synonyms = Molecule::select('synonyms')->where('identifier', $get('../../mol_id_csv'))->get()[0]['synonyms'];
                                                $matched_synonyms = [];
                                                $associative_matched_synonyms = [];
                                                foreach ($synonyms as $synonym) {
                                                    str_contains(strtolower($synonym), strtolower($search)) ? array_push($matched_synonyms, $synonym) : null;
                                                }
                                                foreach ($matched_synonyms as $item) {
                                                    $associative_matched_synonyms[$item] = $item;
                                                }

                                                return $associative_matched_synonyms;
                                            })
                                            ->hidden(function (Get $get) {
                                                return $get('operation') == 'add';
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            })
                                            ->columnSpan(2),
                                        Grid::make('new_synonym_details')
                                            ->schema([
                                                TagsInput::make('new_synonym')
                                                    ->label('New Synonym')
                                                    ->separator(',')
                                                    ->splitKeys([',']),
                                            ])->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(7),
                            ]),
                        Tabs\Tab::make('identifiers')
                            ->label('Identifiers')
                            ->schema([
                                Repeater::make('identifiers_changes')
                                    ->schema([
                                        Checkbox::make('approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            }),
                                        Select::make('change')
                                            ->options([
                                                'name' => 'Name',
                                                'cas' => 'CAS',
                                            ])
                                            ->default('name')
                                            ->live(),
                                        Select::make('current_Name')
                                            ->options(function (Get $get): array {
                                                $name = Molecule::where('identifier', $get('../../mol_id_csv'))->first()->name ?? '';

                                                return [$name => $name];
                                            })
                                            ->hidden(function (Get $get) {
                                                return $get('change') == 'cas';
                                            })
                                            ->columnSpan(2),
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->hidden(function (Get $get) {
                                                return $get('change') == 'name';
                                            })
                                            ->live()
                                            ->columnSpan(2),
                                        Select::make('current_cas')
                                            ->label('Current CAS')
                                            ->options(function (Get $get): array {
                                                $cas_ids = Molecule::where('identifier', $get('../../mol_id_csv'))->first()->cas ?? '';
                                                $associative_cas_ids = [];
                                                foreach ($cas_ids as $item) {
                                                    $associative_cas_ids[$item] = $item;
                                                }

                                                return $associative_cas_ids;
                                            })
                                            ->hidden(function (Get $get) {
                                                return $get('change') == 'name' || $get('operation') == 'add';
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('new_name')
                                            ->label(function (Get $get) {
                                                return $get('change') == 'name' ? 'New Name' : 'New CAS';
                                            })
                                            ->hidden(function (Get $get) {
                                                return $get('operation') == 'remove';
                                            })
                                            ->columnSpan(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(7),
                            ]),
                        Tabs\Tab::make('citations')
                            ->label('Citations')
                            ->schema([
                                Repeater::make('citations_changes')
                                    ->schema([
                                        Checkbox::make('approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            }),
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live()
                                            ->columnSpan(2),
                                        Select::make('citations')
                                            ->label('Citation')
                                            ->searchable()
                                            ->searchDebounce(500)
                                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                                return Molecule::where('identifier', $get('../../mol_id_csv'))->get()[0]->citations()->where('title', 'ilike', "%{$search}%")->limit(10)->pluck('citations.title', 'citations.id')->toArray();
                                            })
                                            ->getOptionLabelUsing(fn ($value): ?string => Citation::find($value)?->name)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') == 'add';
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            })
                                            ->columnSpan(2),
                                        Grid::make('new_citation_details')
                                            ->schema(Citation::getForm())->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(7),

                            ]),

                        Tabs\Tab::make('Chemical Classifications')
                            ->schema([
                                Checkbox::make('approve')
                                    ->inline(false)
                                    ->hidden(function (string $operation) {
                                        return ! auth()->user()->roles()->exists() || $operation == 'create';
                                    }),
                                // ...
                            ]),
                    ])
                    ->hidden(function (Get $get) {
                        return ! $get('is_change');
                    }),
                TextInput::make('doi')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide the DOI link to the resource you are reporting so as to help curators verify.')
                    ->label('DOI')
                    ->url()
                    ->suffixAction(
                        fn (?string $state): Action => Action::make('visit')
                            ->icon('heroicon-s-link')
                            ->url(
                                $state,
                                shouldOpenInNewTab: true,
                            ),
                    ),
                Select::make('collections')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the Collections you want to report. This will help our Curators in reviewing your report.')
                    ->relationship('collections', 'title')
                    ->multiple()
                    ->preload()
                    ->required(function (Get $get) {
                        if ($get('report_type') == 'collection') {
                            return true;
                        }
                    })
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation != 'create') {
                            if ($get('collections') == []) {
                                return true;
                            }
                        } elseif (! request()->has('collection_uuid') && $get('report_type') != 'collection') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    })
                    ->searchable(),
                Select::make('citations')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the Citations you want to report. This will help our Curators in reviewing your report.')
                    ->relationship('citations', 'title')
                    ->options(function () {
                        return Citation::whereNotNull('title')->pluck('title', 'id');
                    })
                    ->multiple()
                    ->required(function (Get $get) {
                        if ($get('report_type') == 'citation') {
                            return true;
                        }
                    })
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation != 'create') {
                            if ($get('citations') == []) {
                                return true;
                            }
                        } elseif (! request()->has('citation_id') && $get('report_type') != 'citation') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    })
                    ->searchable(),
                Select::make('organisms')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the Organisms you want to report. This will help our Curators in reviewing your report.')
                    ->relationship('organisms', 'name')
                    ->multiple()
                    ->searchable()
                    ->required(function (Get $get) {
                        if ($get('report_type') == 'organism') {
                            return true;
                        }
                    })
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation != 'create') {
                            if ($get('organisms') == []) {
                                return true;
                            }
                        } elseif (! request()->has('organism_id') && $get('report_type') != 'organism') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    })
                    ->searchable(),
                Textarea::make('mol_id_csv')
                    ->label('Molecules')
                    ->placeholder('Enter the Identifiers separated by commas')
                    ->required(function (Get $get) {
                        if ($get('report_type') == 'molecule') {
                            return true;
                        }
                    })
                    ->live()
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation != 'create') {
                            return true;
                        } elseif (! request()->has('compound_id') && $get('report_type') != 'molecule') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    }),
                // SpatieTagsInput::make('tags')
                //     ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide comma separated search terms that would help in finding your report when searched.')
                //     ->splitKeys(['Tab', ','])
                //     ->type('reports'),
                Textarea::make('comment')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide your comments/observations on anything noteworthy in the Curation process.')
                    ->hidden(function () {
                        return ! auth()->user()->hasRole('curator');
                    }),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->wrap()
                    ->description(fn (Report $record): string => Str::of($record->evidence)->words(10)),
                Tables\Columns\TextColumn::make('name')->searchable()
                    ->formatStateUsing(
                        fn (Report $record): HtmlString => new HtmlString("<strong>DOI:</strong> {$record->doi}")
                    )
                    ->description(fn (Report $record): string => $record->comment ?? '')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        return auth()->user()->roles()->exists() && $record['status'] == 'submitted';
                    }),
                Tables\Actions\Action::make('approve')
                    // ->button()
                    ->hidden(function (Report $record) {
                        return ! auth()->user()->roles()->exists() || $record['status'] == 'draft' || $record['status'] == 'rejected' || $record['status'] == 'approved';
                    })
                    ->form([
                        Textarea::make('reason'),
                    ])
                    ->action(function (array $data, Report $record, Molecule $molecule, $livewire): void {
                        self::approveReport($data, $record, $molecule, $livewire);
                    }),
                Tables\Actions\Action::make('reject')
                    // ->button()
                    ->color('danger')
                    ->hidden(function (Report $record) {
                        return ! auth()->user()->roles()->exists() || $record['status'] == 'draft' || $record['status'] == 'rejected' || $record['status'] == 'approved';
                    })
                    ->form([
                        Textarea::make('reason'),

                    ])
                    ->action(function (array $data, Report $record): void {
                        self::rejectReport($data, $record);
                    }),
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
            RelationManagers\MoleculesRelationManager::class,
            RelationManagers\CollectionsRelationManager::class,
            RelationManagers\CitationsRelationManager::class,
            RelationManagers\OrganismsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'view' => Pages\ViewReport::route('/{record}'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }

    // Define the Eloquent query for retrieving records based on user roles
    public static function getEloquentQuery(): Builder
    {
        if (! auth()->user()->roles()->exists()) {
            return parent::getEloquentQuery()->where('user_id', auth()->id());
        }

        return parent::getEloquentQuery();
    }

    public static function approveReport(array $data, Report $record, Molecule $molecule, $livewire): void
    {
        $record['status'] = 'approved';
        $record['comment'] = $data['reason'];

        // In case of reporting a synthetic molecule, Deactivate Molecules
        if ($record['mol_id_csv'] && ! $record['is_change']) {
            $molecule_ids = explode(',', $record['mol_id_csv']);
            $molecule = Molecule::whereIn('id', $molecule_ids)->get();
            foreach ($molecule as $mol) {
                $mol->active = false;
                $mol->save();
            }
        }

        // In case of Changes, run SQL queries for the approved changes
        if ($record['is_change']) {

            // To remove null values from the arrays before we assign them below
            // dd(array_map(function($subArray) {
            //     return array_filter($subArray, function($value) {
            //         return !is_null($value);
            //     });
            // }, $livewire->data['organisms_changes']));

            $suggestedChanges = $record->suggested_changes;
            $suggestedChanges['organisms_changes'] = $livewire->data['organisms_changes'];
            $suggestedChanges['geo_locations_changes'] = $livewire->data['geo_locations_changes'];
            $suggestedChanges['synonyms_changes'] = $livewire->data['synonyms_changes'];
            $suggestedChanges['identifiers_changes'] = $livewire->data['identifiers_changes'];
            $suggestedChanges['citations_changes'] = $livewire->data['citations_changes'];
            $record->suggested_changes = $suggestedChanges;
        }

        // Run SQL queries for the approved changes
        self::runSQLQueries($record);

        // Save the report record in any case
        $record->save();
    }

    public static function rejectReport(array $data, Report $record): void
    {
        $record['status'] = 'rejected';
        $record['comment'] = $data['reason'];
        $record->save();
    }

    public static function runSQLQueries(Report $record): void
    {
        DB::transaction(function () use ($record) {
            // Check if organisms_changes exists and process it
            if (isset($record->suggested_changes['organisms_changes'])) {
                foreach ($record->suggested_changes['organisms_changes'] as $organism) {
                    if ($organism['approve'] && $organism['operation'] === 'update' && ! is_null($organism['organisms'])) {
                        // Perform update only if organisms ID is not null
                        //  --------check if the organism name already exists
                        //  --------need to handle iri and other fields of Organism for authentication
                        $db_organism = Organism::find($organism['organisms']);
                        $db_organism->name = $organism['name'];
                        $db_organism->save();
                    } elseif ($organism['approve'] && $organism['operation'] === 'remove' && ! is_null($organism['organisms'])) {
                        // Perform delete only if organisms ID is not null
                        $db_organism = Organism::find($organism['organisms']);
                        $db_organism->delete();
                    } elseif ($organism['approve'] && $organism['operation'] === 'add' && ! is_null($organism['name'])) {
                        // Perform insert only if name is not null (and other required fields can be checked too)
                        Organism::create([
                            'name' => $organism['name'],
                            'iri' => $organism['iri'],
                            'rank' => $organism['rank'],
                        ]);
                    }
                }
            }

            // Check if geo_locations_changes exists and process it
            if (isset($record->suggested_changes['geo_locations_changes'])) {
                foreach ($record->suggested_changes['geo_locations_changes'] as $geo_location) {
                    if ($geo_location['approve'] && $geo_location['operation'] === 'update' && ! is_null($geo_location['geo_locations'])) {
                        // Perform update only if geo_locations ID is not null
                        $db_geo_location = GeoLocation::find($geo_location['geo_locations']);
                        $db_geo_location->name = $geo_location['name'];
                        $db_geo_location->save();
                    } elseif ($geo_location['approve'] && $geo_location['operation'] === 'remove' && ! is_null($geo_location['geo_locations'])) {
                        // Perform delete only if geo_locations ID is not null
                        $db_geo_location = GeoLocation::find($geo_location['geo_locations']);
                        $db_geo_location->delete();
                    } elseif ($geo_location['approve'] && $geo_location['operation'] === 'add' && ! is_null($geo_location['name'])) {
                        // Perform insert only if name is not null
                        GeoLocation::create(['name' => $geo_location['name']]);
                    }
                }
            }

            // Check if synonyms_changes exists and process it
            if (isset($record->suggested_changes['synonyms_changes'])) {
                foreach ($record->suggested_changes['synonyms_changes'] as $synonym) {
                    if ($synonym['approve'] && $synonym['operation'] === 'update' && ! is_null($synonym['synonyms'])) {
                        // Perform update only if synonym name is not null
                        $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                        $synonyms = $db_molecule->synonyms;
                        foreach (array_keys($synonyms, $synonym['synonyms']) as $key) {
                            $synonyms[$key] = $synonym['name'];
                        }
                        $db_molecule->synonyms = $synonyms;
                        $db_molecule->save();
                    } elseif ($synonym['approve'] && $synonym['operation'] === 'remove' && ! is_null($synonym['synonyms'])) {
                        // Perform delete only if synonym name is not null
                        $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                        $synonyms = $db_molecule->synonyms;
                        foreach (array_keys($synonyms, $synonym['synonyms']) as $key) {
                            unset($synonyms[$key]);
                        }
                        $db_molecule->synonyms = $synonyms;
                        $db_molecule->save();
                    } elseif ($synonym['approve'] && $synonym['operation'] === 'add' && ! empty($synonym['new_synonym'])) {
                        // Perform insert only if new_synonym is not empty
                        $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                        $synonyms = $db_molecule->synonyms;
                        $synonyms = array_merge($synonyms, $synonym['new_synonym']);
                        $db_molecule->synonyms = $synonyms;
                        $db_molecule->save();
                    }
                }
            }

            // Check if identifiers_changes exists and process it
            if (isset($record->suggested_changes['identifiers_changes'])) {
                foreach ($record->suggested_changes['identifiers_changes'] as $identifier) {
                    if ($identifier['approve'] && $identifier['change'] === 'name' && ! is_null($identifier['current_Name']) && ! is_null($identifier['new_name'])) {
                        // Update name if current name and new name are not null
                        $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                        $db_molecule->name = $identifier['new_name'];
                        $db_molecule->save();
                    } elseif ($identifier['change'] == 'cas') {
                        if ($identifier['approve'] && $identifier['operation'] === 'update' && ! is_null($identifier['current_cas']) && ! is_null($identifier['new_name'])) {
                            // Update CAS if the current CAS and new name is not null
                            $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                            $cas = $db_molecule->cas;
                            foreach (array_keys($cas, $identifier['current_cas']) as $key) {
                                $cas[$key] = $identifier['new_name'];
                            }
                            $db_molecule->cas = $cas;
                            $db_molecule->save();
                        } elseif ($identifier['approve'] && $identifier['operation'] === 'remove' && ! is_null($identifier['current_cas'])) {
                            // Perform delete only if CAS is not null
                            $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                            $cas = $db_molecule->cas;
                            foreach (array_keys($cas, $identifier['current_cas']) as $key) {
                                unset($cas[$key]);
                            }
                            $db_molecule->cas = $cas;
                            $db_molecule->save();
                        } elseif ($identifier['approve'] && $identifier['operation'] === 'add' && ! is_null($identifier['new_name'])) {
                            // Perform insert only if new_name (CAS) is not null
                            $db_molecule = Molecule::where('identifier', $record->mol_id_csv)->first();
                            $cas = $db_molecule->cas;
                            $cas[] = $identifier['new_name'];
                            $db_molecule->cas = $cas;
                            $db_molecule->save();
                        }
                    }
                }
            }

            // Check if citations_changes exists and process it
            if (isset($record->suggested_changes['citations_changes'])) {
                foreach ($record->suggested_changes['citations_changes'] as $citation) {
                    if ($citation['approve'] && $citation['operation'] === 'update' && ! is_null($citation['citations'])) {
                        // Perform update only if citation ID is not null
                        $db_citation = Citation::find($citation['citations']);
                        // $db_citation->doi = $citation['doi'];
                        $db_citation->title = $citation['name'];
                        // $db_citation->authors = $citation['authors'];
                        // $db_citation->citation_text = $citation['citation_text'];
                        $db_citation->save();
                    } elseif ($citation['approve'] && $citation['operation'] === 'remove' && ! is_null($citation['citations'])) {
                        // Perform delete only if citation ID is not null
                        $db_citation = Citation::find($citation['citations']);
                        $db_citation->delete();
                    } elseif ($citation['approve'] && $citation['operation'] === 'add' && ! is_null($citation['name'])) {
                        // Perform insert only if name (citation ID) is not null
                        $db_citation = new Citation;
                        $db_citation->doi = $citation['doi'];
                        $db_citation->title = $citation['title'];
                        $db_citation->authors = $citation['authors'];
                        $db_citation->citation_text = $citation['citation_text'];
                        $db_citation->save();
                    }
                }
            }
        });
    }
}
