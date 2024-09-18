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
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
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
                                ->hidden(function (Get $get, string $operation) {
                                    return ! auth()->user()->roles()->exists() || $get('status') == 'rejected' || $get('status') == 'approved' || $operation == 'create';
                                })
                                ->form([
                                    Textarea::make('reason'),
                                ])
                                ->action(function (array $data, Report $record, Molecule $molecule, $set): void {

                                    $record['status'] = 'approved';
                                    $record['reason'] = $data['reason'];
                                    $record->save();

                                    $set('status', 'rejected');

                                    if ($record['mol_id_csv'] && ! $record['is_change']) {
                                        $molecule_ids = explode(',', $record['mol_id_csv']);
                                        $molecule = Molecule::whereIn('id', $molecule_ids)->get();
                                        foreach ($molecule as $mol) {
                                            $mol->active = false;
                                            $mol->save();
                                        }
                                    }
                                }),
                            Action::make('reject')
                                ->color('danger')
                                ->hidden(function (Get $get, string $operation) {
                                    return ! auth()->user()->roles()->exists() || $get('status') == 'rejected' || $get('status') == 'approved' || $operation == 'create';
                                })
                                ->form([
                                    Textarea::make('reason'),
                                ])
                                ->action(function (array $data, Report $record, $set): void {

                                    $record['status'] = 'rejected';
                                    $record['reason'] = $data['reason'];
                                    $record->save();

                                    $set('status', 'rejected');
                                }),
                        ])
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
                // KeyValue::make('suggested_changes')
                //     ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter the property (in the left column) and suggested change (in the right column)')
                //     ->addActionLabel('Add property')
                //     ->keyLabel('Property')
                //     ->valueLabel('Suggested change')
                //     ->hidden(function (Get $get) {
                //         return ! $get('is_change');
                //     }),
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('organisms_changes')
                            ->label('Organisms')
                            ->schema([
                                Repeater::make('organisms_changes')
                                    ->schema([
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live(),
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
                                            }),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            }),
                                        Grid::make('new_organism_details')
                                            ->schema(Organism::getForm())->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(4),

                            ]),
                        Tabs\Tab::make('geo_locations_changes')
                            ->label('Geo Locations')
                            ->schema([
                                Repeater::make('geo_locations_changes')
                                    ->schema([
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live(),
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
                                            }),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            }),
                                        Grid::make('new_geo_locations_details')
                                            ->schema(GeoLocation::getForm())->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(4),
                            ]),
                        Tabs\Tab::make('synonyms')
                            ->label('Synonyms')
                            ->schema([
                                Repeater::make('synonyms_changes')
                                    ->schema([
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live(),
                                        Select::make('synonyms')
                                            ->label('Synonym')
                                            ->searchable()
                                            ->searchDebounce(500)
                                            ->getSearchResultsUsing(function (string $search, Get $get): array {
                                                $synonyms = Molecule::select('synonyms')->where('identifier', $get('../../mol_id_csv'))->get()[0]['synonyms'];
                                                $matched_synonyms = [];
                                                foreach ($synonyms as $synonym) {
                                                    str_contains(strtolower($synonym), strtolower($search)) ? array_push($matched_synonyms, $synonym) : null;
                                                }

                                                return $matched_synonyms;
                                            })
                                            ->hidden(function (Get $get) {
                                                return $get('operation') == 'add';
                                            }),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            }),
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
                                    ->columns(4),
                            ]),
                        Tabs\Tab::make('identifiers')
                            ->label('Identifiers')
                            ->schema([
                                Repeater::make('identifiers_changes')
                                    ->schema([
                                        Select::make('identifer_to_change')
                                            ->options([
                                                'name' => 'Name',
                                                'cas' => 'CAS',
                                            ])
                                            ->default('name')
                                            ->live(),
                                        Select::make('current_Name')
                                            ->options(function (Get $get): array {
                                                return [Molecule::where('identifier', $get('../../mol_id_csv'))->get()[0]->name ?? ''];
                                            })
                                            ->default(0)
                                            ->disabled()
                                            ->hidden(function (Get $get) {
                                                return $get('identifer_to_change') == 'cas';
                                            }),
                                        Select::make('current_CAS')
                                            ->options(function (Get $get): array {
                                                return [Molecule::where('identifier', $get('../../mol_id_csv'))->get()[0]->cas];
                                            })
                                            ->default(0)
                                            ->disabled()
                                            ->hidden(function (Get $get) {
                                                return $get('identifer_to_change') == 'name';
                                            }),
                                        TextInput::make('new_name')
                                            ->label(function (Get $get) {
                                                return $get('identifer_to_change') == 'name' ? 'New Name' : 'New CAS';
                                            }),
                                    ])
                                    ->reorderable(false)
                                    ->columns(4),
                            ]),
                        Tabs\Tab::make('citations')
                            ->label('Citations')
                            ->schema([
                                Repeater::make('citations_changes')
                                    ->schema([
                                        Select::make('operation')
                                            ->options([
                                                'update' => 'Update',
                                                'remove' => 'Remove',
                                                'add' => 'Add',
                                            ])
                                            ->default('update')
                                            ->live(),
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
                                            }),
                                        TextInput::make('name')
                                            ->label('Change to')
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'update';
                                            }),
                                        Grid::make('new_citation_details')
                                            ->schema(Citation::getForm())->columns(3)
                                            ->hidden(function (Get $get) {
                                                return $get('operation') != 'add';
                                            })
                                            ->columnStart(2),
                                    ])
                                    ->reorderable(false)
                                    ->columns(4),

                            ]),

                        Tabs\Tab::make('Chemical Classifications')
                            ->schema([
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
                    ->action(function (array $data, Report $record, Molecule $molecule): void {

                        $record['status'] = 'approved';
                        $record['reason'] = $data['reason'];
                        $record->save();

                        if ($record['mol_id_csv'] && ! $record['is_change']) {
                            $molecule_ids = explode(',', $record['mol_id_csv']);
                            $molecule = Molecule::whereIn('id', $molecule_ids)->get();
                            foreach ($molecule as $mol) {
                                $mol->active = false;
                                $mol->save();
                            }
                        }
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

                        $record['status'] = 'rejected';
                        $record['reason'] = $data['reason'];
                        $record->save();
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
}
