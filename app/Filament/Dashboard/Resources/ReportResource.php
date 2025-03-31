<?php

namespace App\Filament\Dashboard\Resources;

use App\Events\ReportAssigned;
use App\Events\ReportStatusChanged;
use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Models\Report;
use App\Models\ReportUser;
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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                                        ->label(function ($record) {
                                            if ($record->status == 'submitted') {
                                                return 'Assign Curator 1';
                                            } else {
                                                return 'Assign Curator 2';
                                            }
                                        })
                                        ->default(function ($record) {
                                            if ($record->status == 'submitted') {
                                                $curator = $record->curators()->wherePivot('curator_number', 1)->first();
                                            } else {
                                                $curator = $record->curators()->wherePivot('curator_number', 2)->first();
                                            }

                                            return $curator?->id;
                                        })
                                        ->options(function (Report $record) {
                                            // Get all users with curator roles
                                            $curators = User::whereHas('roles')->pluck('name', 'id')->toArray();

                                            // For pending reports, filter out the first curator to enforce four-eyes principle
                                            if ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                                                $curator1 = $record->curators()->wherePivot('curator_number', 1)->first();
                                                if ($curator1) {
                                                    unset($curators[$curator1->id]);
                                                }
                                            }

                                            return $curators;
                                        }),
                                ])
                                ->action(function (array $data, Report $record, $livewire): void {
                                    $curator_number = '';
                                    if ($record->status == 'submitted') {
                                        $curator_number = 1;
                                    } else {
                                        $curator_number = 2;
                                    }
                                    $record->curators()->wherePivot('curator_number', $curator_number)->detach();
                                    $record->curators()->attach($data['curator'], [
                                        'curator_number' => $curator_number,
                                    ]);
                                    $record->refresh();
                                    ReportAssigned::dispatch($record, $data['curator']);
                                    if (auth()->id() == $data['curator']) {
                                        $livewire->redirect(ReportResource::getUrl('edit', ['record' => $record->id]));
                                    } else {
                                        $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
                                    }
                                })
                                ->modalHeading('')
                                ->modalSubmitActionLabel('Assign')
                                ->iconButton()
                                ->icon('heroicon-o-user-plus')
                                ->extraAttributes([
                                    'class' => 'ml-1 mr-0',
                                    'title' => 'Assign Curator',
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
                    ->required()
                    ->disabled(function (string $operation) {
                        return $operation != 'create';
                    }),
                TextInput::make('status')
                    ->hidden(function (string $operation) {
                        return $operation == 'create';
                    })
                    ->disabled(),
                Textarea::make('curator_comment')
                    ->label(function ($record) {
                        if ($record->status == 'pending_approval' || $record->status == 'pending_rejection') {
                            return 'Curator 1 Comment';
                        } elseif ($record->status == 'approved' || $record->status == 'rejected') {
                            return 'Curator 2 Comment';
                        }
                    })
                    ->hidden(function ($record) {
                        if ($record?->status == 'pending_approval' || $record?->status == 'pending_rejection') {
                            $curator = $record->curators()->wherePivot('curator_number', 1)->first();

                            return $curator?->pivot->comment ? false : true;
                        }
                        $curator = $record?->curators()->wherePivot('curator_number', 2)->first();

                        return $curator?->pivot->comment ? false : true;
                    })
                    ->disabled(),
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
                                            ->hint("Use '|' (pipe) to separate synonyms")
                                            ->separator('|')
                                            ->splitKeys(['|'])
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
                                            ->hint("Use '|' (pipe) to separate synonyms")
                                            ->separator('|')
                                            ->splitKeys(['|'])
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
                    ->formatStateUsing(fn (Report $record): HtmlString => new HtmlString(
                        '<b>'.$record->title.'</b>'.
                            ' <br> <i>Type: </i>'.
                            ($record->is_change
                                ? '<span class="font-medium">Change</span>'
                                : '<span class=" font-medium">Report</span>'
                            )
                    )),
                // Curator assignment column
                Tables\Columns\TextColumn::make('assigned_curator')
                    ->label('Assigned To')
                    ->state(function (Report $record): ?string {
                        if ($record->status === 'submitted') {
                            $curator = $record->curators()->wherePivot('curator_number', 1)->first();

                            return $curator ? $curator->name : '';
                        } elseif ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                            $curator = $record->curators()->wherePivot('curator_number', 2)->first();

                            return $curator ? $curator->name : '';
                        } elseif ($record->status === 'approved' || $record->status === 'rejected') {
                            $curator = $record->curators()->wherePivot('curator_number', 2)->first();

                            return $curator ? $curator->name : '';
                        }

                        return 'N/A';
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->recordClasses(fn (Model $record) => match ($record->is_change) {
                true => 'bg-teal-50 dark:bg-gray-800',
                default => null,
            })
            ->defaultSort('created_at', 'desc')
            ->filters([
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approveRejectAction')
                    ->label(function (Report $record): string {
                        if ($record->status === 'submitted') {
                            return 'Approve';
                        }

                        if ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                            return 'Confirm';
                        }

                        return 'Resolved';
                    })
                    ->button() // This styles it as a button instead of a badge
                    ->hidden(function (Report $record): bool {
                        // Hide the action if the user doesn't have update permission for the record
                        return ! auth()->user()->can('update', $record);
                    })
                    ->color(function (Report $record): string {
                        // Default color is gray (disabled)
                        $color = 'gray';

                        // Only check for active colors if user is a curator
                        if (
                            auth()->user()->roles()->exists() &&
                            $record->status !== 'approved' &&
                            $record->status !== 'rejected'
                        ) {
                            // For submitted reports
                            if ($record->status === 'submitted') {
                                // Check if user is already assigned as curator 1
                                $isAssignedAsCurator1 = $record->curators()
                                    ->wherePivot('curator_number', 1)
                                    ->wherePivot('user_id', auth()->id())
                                    ->exists();

                                // If curator is already assigned or no curator is assigned yet, active color
                                if (! $record->curators()->wherePivot('curator_number', 1)->exists() || $isAssignedAsCurator1) {
                                    $color = 'primary';
                                }
                            }

                            // For pending reports
                            if ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                                // Check if user was assigned as curator 1 (can't be curator 2 if already curator 1)
                                $wasAssignedAsCurator1 = $record->curators()
                                    ->wherePivot('curator_number', 1)
                                    ->wherePivot('user_id', auth()->id())
                                    ->exists();

                                // Check if user is already assigned as curator 2
                                $isAssignedAsCurator2 = $record->curators()
                                    ->wherePivot('curator_number', 2)
                                    ->wherePivot('user_id', auth()->id())
                                    ->exists();

                                // If user is not curator 1 and either is already curator 2 or no curator 2 is assigned yet, active color
                                if (
                                    ! $wasAssignedAsCurator1 &&
                                    (! $record->curators()->wherePivot('curator_number', 2)->exists() || $isAssignedAsCurator2)
                                ) {
                                    $color = 'warning';
                                }
                            }
                        }

                        return $color;
                    })
                    ->url(function (Report $record): ?string {
                        // Check if curator is allowed to edit this report
                        $canEdit = auth()->user()->roles()->exists() &&
                            $record->status !== 'approved' &&
                            $record->status !== 'rejected';

                        // For submitted reports
                        if ($record->status === 'submitted') {
                            // Check if user is already assigned as curator 1
                            $isAssignedAsCurator1 = $record->curators()
                                ->wherePivot('curator_number', 1)
                                ->wherePivot('user_id', auth()->id())
                                ->exists();

                            // If curator is already assigned or no curator is assigned yet, allow edit
                            if ($canEdit && (! $record->curators()->wherePivot('curator_number', 1)->exists() || $isAssignedAsCurator1)) {
                                return ReportResource::getUrl('edit', ['record' => $record->id]);
                            }
                        }

                        // For pending reports
                        if ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                            // Check if user was assigned as curator 1 (can't be curator 2 if already curator 1)
                            $wasAssignedAsCurator1 = $record->curators()
                                ->wherePivot('curator_number', 1)
                                ->wherePivot('user_id', auth()->id())
                                ->exists();

                            // Check if user is already assigned as curator 2
                            $isAssignedAsCurator2 = $record->curators()
                                ->wherePivot('curator_number', 2)
                                ->wherePivot('user_id', auth()->id())
                                ->exists();

                            // If user is not curator 1 and either is already curator 2 or no curator 2 is assigned yet, allow edit
                            if (
                                $canEdit && ! $wasAssignedAsCurator1 &&
                                (! $record->curators()->wherePivot('curator_number', 2)->exists() || $isAssignedAsCurator2)
                            ) {
                                return ReportResource::getUrl('edit', ['record' => $record->id]);
                            }
                        }

                        // Default to view page if not editable
                        return ReportResource::getUrl('view', ['record' => $record->id]);
                    }),
                Tables\Actions\Action::make('assign_curator')
                    ->label('')
                    ->size('xl')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Radio::make('curator')
                            ->label(function (Report $record) {
                                if ($record->status === 'submitted') {
                                    return 'Assign Curator 1';
                                } elseif ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                                    return 'Assign Curator 2';
                                }

                                return 'Assign Curator';
                            })
                            ->options(function (Report $record) {
                                // Get all users with curator roles
                                $curators = User::whereHas('roles')->pluck('name', 'id')->toArray();

                                // For pending reports, filter out the first curator to enforce four-eyes principle
                                if ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                                    $curator1 = $record->curators()->wherePivot('curator_number', 1)->first();
                                    if ($curator1) {
                                        unset($curators[$curator1->id]);
                                    }
                                }

                                return $curators;
                            })
                            ->default(function (Report $record) {
                                // Pre-select current curator if assigned
                                if ($record->status === 'submitted') {
                                    $curator = $record->curators()->wherePivot('curator_number', 1)->first();

                                    return $curator?->id;
                                } elseif ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                                    $curator = $record->curators()->wherePivot('curator_number', 2)->first();

                                    return $curator?->id;
                                }

                                return null;
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, Report $record): void {
                        $curatorNumber = 1; // Default

                        if ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                            $curatorNumber = 2;
                        }

                        // Remove any existing curator for this position
                        $record->curators()->wherePivot('curator_number', $curatorNumber)->detach();

                        // Assign the new curator
                        $record->curators()->attach($data['curator'], [
                            'curator_number' => $curatorNumber,
                        ]);

                        // Dispatch event
                        ReportAssigned::dispatch($record, $data['curator']);
                    })
                    ->modalHeading(function (Report $record) {
                        if ($record->status === 'submitted') {
                            return 'Assign Curator 1';
                        } elseif ($record->status === 'pending_approval' || $record->status === 'pending_rejection') {
                            return 'Assign Curator 2';
                        }

                        return 'Assign Curator';
                    })
                    ->modalSubmitActionLabel('Assign')
                    ->hidden(function (Report $record): bool {
                        // Hide for non-curators or approved/rejected reports
                        return ! auth()->user()->roles()->exists() ||
                            $record->status === 'approved' ||
                            $record->status === 'rejected';
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
        $status = '';
        $curator_number = '';
        if (! $record['is_change']) {
            if ($record['status'] == 'pending_approval' || $record['status'] == 'pending_rejection') {
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
                $status = 'approved';
                $curator_number = 2;
            } else {
                $status = 'pending_approval';
                $curator_number = 1;
            }
        } else {
            // This has to be here so as to log the audit changes properly between the 1st and 2nd approvals.
            self::$overall_changes = getOverallChanges(self::$approved_changes);

            // In case of Changes
            if ($record['status'] == 'pending_approval' || $record['status'] == 'pending_rejection') {
                // Run SQL queries for the approved changes
                self::runSQLQueries($record);
                $status = 'approved';
                $curator_number = 2;
            } else {
                $status = 'pending_approval';
                $curator_number = 1;
            }

            $suggested_changes = $record['suggested_changes'];

            $suggested_changes['curator']['approved_changes'] = self::$overall_changes;
            $record['suggested_changes'] = $suggested_changes;
            $formData = copyChangesToCuratorJSON($record, $livewire->data);
            $suggested_changes['curator'] = $formData['suggested_changes']['curator'];
            $record['suggested_changes'] = $suggested_changes;

            $record->save();
            $record->refresh();
        }
        // Check if curator is already assigned
        $pivot = ReportUser::where('report_id', $record->id)
            ->where('user_id', auth()->id())
            ->where('curator_number', $curator_number)
            ->first();

        // If not assigned yet, assign curator based on report status
        if (! $pivot) {
            if ($record['status'] == 'submitted') {
                // For first approval, automatically assign current user as curator 1
                $record->curators()->wherePivot('curator_number', 1)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 1,
                    'status' => $status,
                    'comment' => $data['reason'],
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            } elseif ($record['status'] == 'pending_approval' || $record['status'] == 'pending_rejection') {
                // For second approval, automatically assign current user as curator 2
                $record->curators()->wherePivot('curator_number', 2)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 2,
                    'status' => $status,
                    'comment' => $data['reason'],
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            }
        } else {
            // Update existing pivot record
            $pivot->status = $status;
            $pivot->comment = $data['reason'];
            $pivot->save();
        }
        $record['status'] = $status;
        $record->save();
        $record->refresh();
        ReportStatusChanged::dispatch($record);

        // Show appropriate notification based on action
        if ($status == 'pending_approval') {
            Notification::make()
                ->title('Report approved for first review')
                ->body('The report has been approved in first review and is now pending final approval.')
                ->success()
                ->send();
        } elseif ($status == 'approved') {
            Notification::make()
                ->title('Report fully approved')
                ->body('The report has been fully approved.')
                ->success()
                ->send();
        }

        $livewire->redirect(ReportResource::getUrl('index'));
        // $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
    }

    public static function rejectReport(array $data, Report $record, $livewire): void
    {
        $status = '';
        $curator_number = '';
        if ($record['status'] == 'pending_approval' || $record['status'] == 'pending_rejection') {
            $status = 'rejected';
            $curator_number = 2;
        } else {
            $status = 'pending_rejection';
            $curator_number = 1;
        }
        // Check if curator is already assigned
        $pivot = ReportUser::where('report_id', $record->id)
            ->where('user_id', auth()->id())
            ->where('curator_number', $curator_number)
            ->first();

        // If not assigned yet, assign curator based on report status
        if (! $pivot) {
            if ($record['status'] == 'submitted') {
                // For first rejection, automatically assign current user as curator 1
                $record->curators()->wherePivot('curator_number', 1)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 1,
                    'status' => $status,
                    'comment' => $data['reason'],
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            } elseif ($record['status'] == 'pending_approval' || $record['status'] == 'pending_rejection') {
                // For second rejection, automatically assign current user as curator 2
                $record->curators()->wherePivot('curator_number', 2)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 2,
                    'status' => $status,
                    'comment' => $data['reason'],
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            }
        } else {
            // Update existing pivot record
            $pivot->status = $status;
            $pivot->comment = $data['reason'];
            $pivot->save();
        }
        $record['status'] = $status;
        $record->save();

        ReportStatusChanged::dispatch($record);

        // Show appropriate notification based on action
        if ($status == 'pending_rejection') {
            Notification::make()
                ->title('Report rejected in first review')
                ->body('The report has been rejected in first review and is now pending final rejection.')
                ->warning()
                ->send();
        } elseif ($status == 'rejected') {
            Notification::make()
                ->title('Report fully rejected')
                ->body('The report has been rejected.')
                ->danger()
                ->send();
        }

        $livewire->redirect(ReportResource::getUrl('index'));
        // $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
    }

    public static function runSQLQueries(Report $record): void
    {
        DB::transaction(function () use ($record) {

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
                    $synonyms = explode('|', self::$overall_changes['synonym_changes']['add']);
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
