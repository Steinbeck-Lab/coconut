<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Models\Report;
use App\Models\User;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
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
use Filament\Tables\Actions\Action as TableAction;
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

    protected static $molecule = null;

    protected static $approved_changes = null;

    protected static $overall_changes = null;

    public function __construct()
    {
        self::$molecule = request()->has('compound_id') ? Molecule::where('identifier', request()->compound_id)->first() : null;
    }

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
                                false => 'Report',
                                true => 'Request Changes',
                            ])
                            ->inline()
                            ->hidden(function (string $operation) {
                                return $operation == 'create';
                            })
                            ->disabled(function (string $operation) {
                                return $operation == 'edit';
                            })
                            ->columnSpan(2),

                        Actions::make([
                            Action::make('approve')
                                ->form(function ($record, $livewire, $get) {
                                    if ($record['is_change']) {
                                        self::$approved_changes = self::prepareApprovedChanges($record, $livewire);
                                        $key_value_fields = getChangesToDisplayModal(self::$approved_changes);
                                        array_unshift(
                                            $key_value_fields,
                                            Textarea::make('reason')
                                                ->helperText(function ($get) {
                                                    if (count(self::$approved_changes) <= 1) {
                                                        return new HtmlString('<span style="color:red"> You must select at least one change to approve </span>');
                                                    } else {
                                                        return null;
                                                    }
                                                })
                                                ->disabled(count(self::$approved_changes) <= 1)
                                        );

                                        return $key_value_fields;
                                    } else {
                                        if ($get('report_type') == 'molecule') {
                                            return [
                                                Textarea::make('reason')
                                                    ->required(),
                                            ];
                                        } else {
                                            return [
                                                Textarea::make('reason')
                                                    ->required()
                                                    ->helperText(new HtmlString('<span style="color:red">Make sure you manually made all the changes requested before approving. This will only change the status of the report.</span>')),
                                            ];
                                        }
                                    }
                                })
                                ->hidden(function (Get $get, string $operation) {
                                    return ! auth()->user()->roles()->exists() || $get('status') == 'rejected' || $get('status') == 'approved' || $operation != 'edit';
                                })
                                ->action(function (array $data, Report $record, Molecule $molecule, $set, $livewire, $get): void {
                                    self::approveReport($data, $record, $molecule, $livewire);
                                    $set('status', 'approved');
                                })
                                ->modalSubmitAction(function () {
                                    if (! empty(self::$approved_changes) && count(self::$approved_changes) <= 1) {
                                        return false;
                                    }
                                }),
                            Action::make('reject')
                                ->color('danger')
                                ->form([
                                    Textarea::make('reason'),
                                ])
                                ->hidden(function (Get $get, string $operation) {
                                    return ! auth()->user()->roles()->exists() || $get('status') == 'rejected' || $get('status') == 'approved' || $operation != 'edit';
                                })
                                ->action(function (array $data, Report $record, $set, $livewire): void {
                                    self::rejectReport($data, $record, $livewire);
                                    $set('status', 'rejected');
                                }),
                            Action::make('viewCompoundPage')
                                ->color('info')
                                ->url(fn (string $operation, $record): string => $operation === 'create' ? env('APP_URL').'/compounds/'.request()->compound_id : env('APP_URL').'/compounds/'.$record->mol_id_csv)
                                ->openUrlInNewTab()
                                ->hidden(function (Get $get, string $operation) {
                                    return ! $get('type');
                                }),
                            Action::make('assign')
                                ->hidden(function (Get $get, string $operation, ?Report $record) {
                                    return ! (auth()->user()->roles()->exists() && ($operation == 'view' || $operation == 'edit') && ($record->status != 'approved') && ($record->status != 'rejected'));
                                })
                                ->form([
                                    Radio::make('curator')
                                        ->label('Choose a curator')
                                        ->default(function ($record) {
                                            return $record->assigned_to;
                                        })
                                        ->options(function () {
                                            return $users = User::whereHas('roles')->pluck('name', 'id');
                                        }),
                                ])
                                ->action(function (array $data, Report $record, $livewire): void {
                                    $record['assigned_to'] = $data['curator'];
                                    $record->save();
                                    $record->refresh();
                                    if (auth()->id() == $data['curator']) {
                                        $livewire->redirect(ReportResource::getUrl('edit', ['record' => $record->id]));
                                    } else {
                                        $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
                                    }
                                })
                                ->modalHeading('')
                                ->modalSubmitActionLabel('Assign')
                                ->iconButton()
                                ->icon('heroicon-m-user-group')
                                ->extraAttributes([
                                    'class' => 'ml-1 mr-0',
                                ])
                                ->size('xl'),
                        ])
                            // ->hidden(function (Get $get) {
                            //     return $get('report_type') != 'molecule';
                            // })
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
                    ->hidden(function (string $operation, $get) {
                        return $operation != 'create' || $get('type');
                    }),
                TextInput::make('title')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Title of the report. This is required.')
                    ->default(function ($get) {
                        if ($get('type') == 'change' && request()->has('compound_id')) {
                            return 'Request changes to '.request()->compound_id;
                        }
                    })
                    ->required(),
                Textarea::make('evidence')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Please provide Evidence/Comment to support your claims in this report. This will help our Curators in reviewing your report.')
                    ->label('Evidence/Comment')
                    ->hidden(function (Get $get) {
                        return $get('is_change');
                    }),
                Tabs::make('suggested_changes')
                    ->tabs([
                        Tabs\Tab::make('compound_info_changes')
                            ->label('Compound Info')
                            ->schema([
                                Fieldset::make('Geo Locations')
                                    ->schema([
                                        Checkbox::make('approve_geo_locations')
                                            ->label('Approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpan(1),
                                        Select::make('existing_geo_locations')
                                            ->label('Existing')
                                            ->multiple()
                                            ->options(function (): array {
                                                $geo_locations = [];
                                                if (self::$molecule) {
                                                    $geo_locations = self::$molecule->geo_locations->pluck('name', 'id')->toArray();
                                                }

                                                return $geo_locations;
                                            })
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_geo_location_existing') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                        TagsInput::make('new_geo_locations')
                                            ->label('New')
                                            ->separator(',')
                                            ->splitKeys([','])
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_geo_location_new') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                    ])
                                    ->columns(9),
                                Fieldset::make('Synonyms')
                                    ->schema([
                                        Checkbox::make('approve_synonyms')
                                            ->label('Approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpan(1),
                                        Select::make('existing_synonyms')
                                            ->label('Existing')
                                            ->multiple()
                                            ->options(function (): array {
                                                $synonyms = [];
                                                if (self::$molecule) {
                                                    $synonyms = self::$molecule->synonyms;
                                                }

                                                return empty($synonyms) ? [] : $synonyms;
                                            })
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_synonym_existing') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                        TagsInput::make('new_synonyms')
                                            ->label('New')
                                            ->hint("Use '|' (pipe) to separate synonyms")
                                            ->separator('|')
                                            ->splitKeys(['|'])
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_synonym_new') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                    ])
                                    ->columns(9),
                                Fieldset::make('Name')
                                    ->schema([
                                        Checkbox::make('approve_name')
                                            ->label('Approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpan(1),
                                        Textarea::make('name')
                                            ->default(function () {
                                                if (self::$molecule) {
                                                    return self::$molecule->name;
                                                }
                                            })
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_name_change') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                    ])
                                    ->columns(9),
                                Fieldset::make('CAS')
                                    ->schema([
                                        Checkbox::make('approve_cas')
                                            ->label('Approve')
                                            ->inline(false)
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpan(1),
                                        Select::make('existing_cas')
                                            ->label('Existing')
                                            ->multiple()
                                            ->options(function () {
                                                if (self::$molecule) {
                                                    return self::$molecule->cas;
                                                }
                                            })
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_cas_existing') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                        TagsInput::make('new_cas')
                                            ->label('New')
                                            ->separator(',')
                                            ->splitKeys([','])
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_cas_new') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(4),
                                    ])
                                    ->columns(9),
                            ]),
                        Tabs\Tab::make('organisms_changes')
                            ->label('Organisms')
                            ->schema([
                                Fieldset::make('Organisms')
                                    ->schema([
                                        Checkbox::make('approve_existing_organisms')
                                            ->label('Approve')
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpanFull(),
                                        Select::make('existing_organisms')
                                            ->label('Existing')
                                            ->multiple()
                                            ->options(function () {
                                                if (self::$molecule) {
                                                    return self::$molecule->organisms->pluck('name', 'id')->toArray();
                                                }
                                            })
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_organism_existing') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(9),
                                    ])
                                    ->columns(9),

                                Repeater::make('new_organisms')
                                    ->label('')
                                    ->schema([
                                        Checkbox::make('approve_new_organism')
                                            ->label('Approve')
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpanFull(),
                                        Grid::make('new_organism')
                                            ->schema(Organism::getForm())->columns(4),
                                    ])
                                    ->reorderable(false)
                                    ->addActionLabel('Add New Organism')
                                    ->defaultItems(0)
                                    ->disabled(function (Get $get, string $operation) {
                                        return ! $get('show_organism_new') && $operation == 'edit';
                                    })
                                    ->dehydrated()
                                    ->columns(9),

                            ]),
                        Tabs\Tab::make('citations')
                            ->label('Citations')
                            ->schema([
                                Fieldset::make('Citations')
                                    ->schema([
                                        Checkbox::make('approve_existing_citations')
                                            ->label('Approve')
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpanFull(),
                                        Select::make('existing_citations')
                                            ->label('Existing')
                                            ->multiple()
                                            ->options(function () {
                                                if (self::$molecule) {
                                                    return self::$molecule->citations->where('title', '!=', null)->pluck('title', 'id')->toArray();
                                                }
                                            })
                                            ->disabled(function (Get $get, string $operation) {
                                                return ! $get('show_citation_existing') && $operation == 'edit';
                                            })
                                            ->dehydrated()
                                            ->columnSpan(9),
                                    ])
                                    ->columns(9),
                                Repeater::make('new_citations')
                                    ->label('')
                                    ->schema([
                                        Checkbox::make('approve_new_citation')
                                            ->label('Approve')
                                            ->hidden(function (string $operation) {
                                                return ! auth()->user()->roles()->exists() || $operation == 'create';
                                            })
                                            ->columnSpanFull(),
                                        Grid::make('new_citation')
                                            ->schema(Citation::getForm())->columns(4),
                                    ])
                                    ->reorderable(false)
                                    ->addActionLabel('Add New Citation')
                                    ->defaultItems(0)
                                    ->disabled(function (Get $get, string $operation) {
                                        return ! $get('show_citation_new') && $operation == 'edit';
                                    })
                                    ->dehydrated()
                                    ->columns(9),

                            ]),
                    ])
                    ->hidden(function (Get $get) {
                        return ! $get('is_change');
                    }),
                TextInput::make('doi')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide the DOI link to the resource you are reporting so as to help curators verify.')
                    ->label('DOI')
                    ->suffixAction(
                        fn (?string $state): Action => Action::make('visit')
                            ->icon('heroicon-s-link')
                            ->url(
                                $state,
                                shouldOpenInNewTab: true,
                            ),
                    )
                    ->hidden(function (string $operation, $get, ?Report $record) {
                        return true;
                        // return $operation == 'create' && $get('type') && $get('type') == 'change' ? true : false;
                    }),
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
                        if ($operation != 'create' || $get('type')) {
                            return true;
                        }
                        if (! request()->has('compound_id') && $get('report_type') != 'molecule') {
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
                TextColumn::make('is_change')
                    ->label('Type')
                    ->badge()
                    ->color(fn (Report $record): string => $record->is_change ? 'warning' : 'gray')
                    ->formatStateUsing(function (Report $record): string {
                        return $record->is_change ? 'change' : 'report';
                    }),
                Tables\Columns\TextColumn::make('curator.name')
                    ->searchable()
                    ->placeholder('Choose a curator')
                    ->action(
                        TableAction::make('select')
                            ->label('')
                            ->form([
                                Radio::make('curator')
                                    ->label('Choose a curator')
                                    ->default(function ($record) {
                                        return $record->assigned_to;
                                    })
                                    ->options(function () {
                                        return $users = User::whereHas('roles')->pluck('name', 'id');
                                    }),
                            ])
                            ->action(function (array $data, Report $record): void {
                                $record['assigned_to'] = $data['curator'];
                                $record->save();
                                $record->refresh();
                            })
                            ->modalSubmitActionLabel('Assign')
                            ->modalHidden(fn (Report $record): bool => ! auth()->user()->roles()->exists() || $record['status'] == 'approved' || $record['status'] == 'rejected'),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        //     return auth()->user()->roles()->exists() && $record['status'] == 'submitted';
                        // }),
                        // Tables\Actions\Action::make('approve')
                        //     // ->button()
                        //     ->hidden(function (Report $record) {
                        //         return ! auth()->user()->roles()->exists() || $record['status'] == 'draft' || $record['status'] == 'rejected' || $record['status'] == 'approved';
                        //     })
                        //     ->form([
                        //         Textarea::make('reason'),
                        //     ])
                        //     ->action(function (array $data, Report $record, Molecule $molecule, $livewire): void {
                        //         self::approveReport($data, $record, $molecule, $livewire);
                        //     }),
                        // Tables\Actions\Action::make('reject')
                        //     // ->button()
                        //     ->color('danger')
                        //     ->hidden(function (Report $record) {
                        //         return ! auth()->user()->roles()->exists() || $record['status'] == 'draft' || $record['status'] == 'rejected' || $record['status'] == 'approved';
                        //     })
                        //     ->form([
                        //         Textarea::make('reason'),

                        //     ])
                        //     ->action(function (array $data, Report $record): void {
                        //         self::rejectReport($data, $record, $livewire);
                        //     }),
                        return auth()->user()->roles()->exists() && $record['status'] == 'submitted' && ($record['assigned_to'] == null || $record['assigned_to'] == auth()->id());
                    }),
                Tables\Actions\ViewAction::make(),
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

    public static function prepareApprovedChanges(Report $record, $livewire)
    {
        $approved_changes = [];

        $approved_changes['mol_id_csv'] = $record['mol_id_csv'];
        if ($record['is_change']) {

            if ($livewire->data['approve_geo_locations']) {
                $approved_changes['existing_geo_locations'] = $livewire->data['existing_geo_locations'];
                $approved_changes['new_geo_locations'] = $livewire->data['new_geo_locations'];
            }

            if ($livewire->data['approve_synonyms']) {
                $approved_changes['existing_synonyms'] = $livewire->data['existing_synonyms'];
                $approved_changes['new_synonyms'] = $livewire->data['new_synonyms'];
            }

            if ($livewire->data['approve_name']) {
                $approved_changes['name'] = $livewire->data['name'];
            }

            if ($livewire->data['approve_cas']) {
                $approved_changes['existing_cas'] = $livewire->data['existing_cas'];
                $approved_changes['new_cas'] = $livewire->data['new_cas'];
            }

            if ($livewire->data['approve_existing_organisms']) {
                $approved_changes['existing_organisms'] = $livewire->data['existing_organisms'];
            }
            if (count($livewire->data['new_organisms']) > 0) {
                foreach ($livewire->data['new_organisms'] as $key => $organism) {
                    if ($organism['approve_new_organism']) {
                        $approved_changes['new_organisms'][] = $organism;
                    }
                }
            }

            if ($livewire->data['approve_existing_citations']) {
                $approved_changes['existing_citations'] = $livewire->data['existing_citations'];
            }
            if (count($livewire->data['new_citations']) > 0) {
                foreach ($livewire->data['new_citations'] as $key => $citation) {
                    if ($citation['approve_new_citation']) {
                        $approved_changes['new_citations'][] = $citation;
                    }
                }
            }
        }

        return $approved_changes;
    }

    public static function approveReport(array $data, Report $record, Molecule $molecule, $livewire): void
    {
        // In case of reporting a synthetic molecule, Deactivate Molecules
        if (! $record['is_change']) {
            if ($record['report_type'] == 'molecule') {
                $molecule_ids = explode(',', $record['mol_id_csv']);
                $molecule = Molecule::whereIn('identifier', $molecule_ids)->get();
                foreach ($molecule as $mol) {
                    $mol->active = false;
                    $mol->status = 'REVOKED';
                    $mol->comment = prepareComment($data['reason']);
                    $mol->save();
                }
            }
            $record['status'] = 'approved';
            $record['comment'] = prepareComment($data['reason']);
            $record['assigned_to'] = auth()->id();
            $record->save();
        } else {
            // In case of Changes
            // Run SQL queries for the approved changes
            self::runSQLQueries($record);

            $suggested_changes = $record['suggested_changes'];

            $suggested_changes['curator']['approved_changes'] = self::$overall_changes;
            $record['suggested_changes'] = $suggested_changes;
            $record->comment = prepareComment($data['reason']);
            $record['status'] = 'approved';
            $formData = copyChangesToCuratorJSON($record, $livewire->data);
            $suggested_changes['curator'] = $formData['suggested_changes']['curator'];
            $record['suggested_changes'] = $suggested_changes;

            $record->save();
        }
        $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
    }

    public static function rejectReport(array $data, Report $record, $livewire): void
    {
        $record['status'] = 'rejected';
        $record['comment'] = $data['reason'];
        $record['assigned_to'] = auth()->id();
        $record->save();

        $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
    }

    public static function runSQLQueries(Report $record): void
    {
        DB::transaction(function () use ($record) {
            self::$overall_changes = getOverallChanges(self::$approved_changes);

            // Check if 'molecule_id' is provided or use a default molecule for association/dissociation
            $molecule = Molecule::where('identifier', $record['mol_id_csv'])->first();

            // Apply Geo Location Changes
            if (array_key_exists('geo_location_changes', self::$overall_changes)) {
                if (! empty(self::$overall_changes['geo_location_changes']['delete'])) {
                    $detachable_geo_locations_ids = GeoLocation::whereIn('name', self::$overall_changes['geo_location_changes']['delete'])->pluck('id')->toArray();
                    $molecule->auditDetach('geo_locations', $detachable_geo_locations_ids);
                }
                if (! empty(self::$overall_changes['geo_location_changes']['add'])) {
                    $geoLocations = explode(',', self::$overall_changes['geo_location_changes']['add']);
                    foreach ($geoLocations as $newLocation) {
                        $geo_location = GeoLocation::firstOrCreate(['name' => $newLocation]);
                        $molecule->auditAttach('geo_locations', $geo_location);
                    }
                }
            }

            // Apply Synonym Changes
            if (array_key_exists('synonym_changes', self::$overall_changes)) {
                $db_synonyms = $molecule->synonyms ?? [];
                if (! empty(self::$overall_changes['synonym_changes']['delete'])) {
                    $db_synonyms = array_diff($db_synonyms, self::$overall_changes['synonym_changes']['delete']);
                    $molecule->synonyms = $db_synonyms;
                }
                if (! empty(self::$overall_changes['synonym_changes']['add'])) {
                    $synonyms = explode(',', self::$overall_changes['synonym_changes']['add']);
                    $db_synonyms = array_merge($db_synonyms, $synonyms);
                    $molecule->synonyms = $db_synonyms;
                }
            }

            // Apply Name Changes
            if (array_key_exists('name_change', self::$overall_changes)) {
                $molecule->name = self::$overall_changes['name_change']['new'];
                $molecule->save();
                $molecule->refresh();
            }

            // Apply CAS Changes
            if (array_key_exists('cas_changes', self::$overall_changes)) {
                $db_cas = $molecule->cas ?? [];
                if (! empty(self::$overall_changes['cas_changes']['delete'])) {
                    $db_cas = array_diff($db_cas, self::$overall_changes['cas_changes']['delete']);
                    $molecule->cas = $db_cas;
                }
                if (! empty(self::$overall_changes['cas_changes']['add'])) {
                    $cas = explode(',', self::$overall_changes['cas_changes']['add']);
                    $db_cas = array_merge($db_cas, $cas);
                    $molecule->cas = $db_cas;
                }
            }

            // Apply Organism Changes: Dissociate from Molecule
            if (array_key_exists('organism_changes', self::$overall_changes)) {
                if (! empty(self::$overall_changes['organism_changes']['delete'])) {
                    $organismIds = Organism::whereIn('name', self::$overall_changes['organism_changes']['delete'])->pluck('id')->toArray();
                    $molecule->auditDetach('organisms', $organismIds);
                }
                if (! empty(self::$overall_changes['organism_changes']['add'])) {
                    foreach (self::$overall_changes['organism_changes']['add'] as $newOrganism) {
                        $organism = Organism::firstOrCreate([
                            'name' => $newOrganism['name'],
                            'iri' => $newOrganism['iri'],
                            'rank' => $newOrganism['rank'],
                            'molecule_count' => 1,
                            'slug' => Str::slug($newOrganism['name']),
                        ]);
                        $molecule->auditAttach('organisms', $organism);
                    }
                }
            }

            // Apply Citation Changes: Dissociate from Molecule
            if (array_key_exists('citation_changes', self::$overall_changes)) {
                if (! empty(self::$overall_changes['citation_changes']['delete'])) {
                    $citations = Citation::whereIn('title', self::$overall_changes['citation_changes']['delete'])->get();
                    $citationIds = $citations->pluck('id')->toArray();
                    $molecule->auditDetach('citations', $citationIds);
                }
                if (! empty(self::$overall_changes['citation_changes']['add'])) {
                    foreach (self::$overall_changes['citation_changes']['add'] as $newCitation) {
                        $citation = Citation::firstOrCreate([
                            'doi' => $newCitation['doi'],
                            'title' => $newCitation['title'],
                            'authors' => $newCitation['authors'],
                            'citation_text' => $newCitation['citation_text'],
                        ]);
                        $molecule->auditAttach('citations', $citation);
                    }
                }
            }

            $molecule->save();
        });
    }
}
