<?php

namespace App\Filament\Dashboard\Resources;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Events\ReportAssigned;
use App\Events\ReportStatusChanged;
use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\Entry;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Models\Report;
use App\Models\ReportUser;
use App\Models\User;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Closure;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        Select::make('report_category')
                            ->label('')
                            ->live()
                            ->default(function ($record) {
                                if ($record) {
                                    return $record->report_category;
                                }
                                $request = request();
                                if ($request->has('compound_id') && $request->type === 'change') {
                                    return ReportCategory::UPDATE->value;
                                } elseif ($request->has('compound_id') && $request->type === 'report') {
                                    return ReportCategory::REVOKE->value;
                                } else {
                                    return ReportCategory::SUBMISSION->value;
                                }
                            })
                            ->options(function ($operation) {
                                $hasParams = count(request()->all()) > 0;
                                $options = [
                                    ReportCategory::REVOKE->value => 'Report',
                                    ReportCategory::SUBMISSION->value => 'New Molecule',
                                ];
                                if ($hasParams || $operation == 'edit' || $operation == 'view') {
                                    $options[ReportCategory::UPDATE->value] = 'Request Changes';
                                }

                                return $options;
                            })
                            ->disabled(function (string $operation) {
                                return $operation == 'edit' || count(request()->all()) > 0;
                            })
                            ->dehydrated()
                            ->columnSpan(2),

                        Actions::make([
                            Action::make('approve')
                                ->form(function ($record, $livewire, $get) {
                                    if ($record['report_category'] === ReportCategory::UPDATE->value) {
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
                                    } elseif ($record['report_category'] === ReportCategory::SUBMISSION->value) {
                                        // Return null for SUBMISSION to use default confirmation modal
                                        return null;
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
                                ->requiresConfirmation(function ($record, $livewire) {

                                    // For new molecule reports, validate before showing confirmation
                                    if ($record['report_category'] === 'new_molecule') {
                                        try {
                                            // Attempt to validate
                                            $livewire->validate();

                                            // If validation passed, show confirmation modal
                                            return true;
                                        } catch (\Illuminate\Validation\ValidationException $e) {
                                            // Validation failed, don't show confirmation modal
                                            // Filament will automatically show validation errors
                                            return false;
                                        }
                                    }

                                    // Only use the default confirmation modal when report_category is 'new_molecule'
                                    return $record['report_category'] === 'new_molecule';
                                })
                                ->hidden(function (Get $get, string $operation) {
                                    return ! auth()->user()->roles()->exists() ||
                                        $get('status') == ReportStatus::REJECTED->value ||
                                        $get('status') == ReportStatus::APPROVED->value ||
                                        $operation != 'edit';
                                })
                                ->action(function (array $data, Report $record, Molecule $molecule, $set, $livewire, $get): void {
                                    // Add this validation check before processing the approval
                                    if ($record['report_category'] === ReportCategory::SUBMISSION->value) {
                                        // Validate the form data
                                        $livewire->validate();

                                        // If validation passes, proceed with approval
                                        self::approveReport($data, $record, $molecule, $livewire);
                                        $set('status', ReportStatus::APPROVED->value);
                                    } else {
                                        // For other report types, proceed as normal
                                        self::approveReport($data, $record, $molecule, $livewire);
                                        $set('status', ReportStatus::APPROVED->value);
                                    }
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
                                    return ! auth()->user()->roles()->exists() ||
                                        $get('status') == ReportStatus::REJECTED->value ||
                                        $get('status') == ReportStatus::APPROVED->value ||
                                        $operation != 'edit';
                                })
                                ->action(function (array $data, Report $record, $set, $livewire): void {
                                    self::rejectReport($data, $record, $livewire);
                                    $set('status', ReportStatus::REJECTED->value);
                                }),
                            Action::make('viewCompoundPage')
                                ->color('info')
                                ->url(fn (string $operation, $record): string => $operation === 'create' ? env('APP_URL').'/compounds/'.request()->compound_id : env('APP_URL').'/compounds/'.$record->mol_ids)
                                ->openUrlInNewTab()
                                ->hidden(function (Get $get, string $operation) {
                                    return ! $get('type');
                                }),
                            Action::make('assign')
                                ->hidden(function (Get $get, string $operation, ?Report $record) {
                                    return ! (auth()->user()->roles()->exists() &&
                                        ($operation == 'view' || $operation == 'edit') &&
                                        ($record->status != ReportStatus::APPROVED->value) &&
                                        ($record->status != ReportStatus::REJECTED->value));
                                })
                                ->form([
                                    Radio::make('curator')
                                        ->label(function ($record) {
                                            if ($record->status == ReportStatus::SUBMITTED->value) {
                                                return 'Assign Curator 1';
                                            } else {
                                                return 'Assign Curator 2';
                                            }
                                        })
                                        ->default(function ($record) {
                                            if ($record->status == ReportStatus::SUBMITTED->value) {
                                                $curator = $record->curators()->wherePivot('curator_number', 1)->first();
                                            } else {
                                                $curator = $record->curators()->wherePivot('curator_number', 2)->first();
                                            }

                                            return $curator?->id;
                                        })
                                        ->options(function (Report $record) {
                                            // Get all users with curator roles
                                            $curators = User::whereHas('roles')->pluck('name', 'id')->toArray();

                                            // For pending reports, remove the first curator to allow other curators to be assigned
                                            if ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
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
                                    if ($record->status == ReportStatus::SUBMITTED->value) {
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
                        return getReportTypes(); // changeit with Enums?
                    })
                    ->hidden(function (string $operation, $get) {
                        return $operation != 'create' || $get('type') || $get('report_category') == ReportCategory::SUBMISSION->value;
                    }),
                TextInput::make('title')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Title of the report. This is required.')
                    ->default(function ($get) {
                        if ($get('report_category') == ReportCategory::UPDATE->value && request()->has('compound_id')) {
                            return 'Request changes to '.request()->compound_id;
                        }
                        if ($get('report_category') == ReportCategory::SUBMISSION->value) {
                            return 'New Molecule Report for:';
                        }
                        if ($get('report_category') == ReportCategory::REVOKE->value && request()->has('compound_id')) {
                            return 'Revoke '.request()->compound_id;
                        }
                    })
                    ->required(function ($get) {
                        return $get('report_category') !== ReportCategory::SUBMISSION->value;
                    }),
                Textarea::make('evidence')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Please provide Evidence/Comment to support your claims in this report. This will help our Curators in reviewing your report.')
                    ->label('Evidence/Comment')
                    ->hidden(function (Get $get) {
                        return $get('report_category') === ReportCategory::UPDATE->value || $get('report_category') === ReportCategory::SUBMISSION->value;
                    }),
                TextInput::make('status')
                    ->hidden(function (string $operation) {
                        return $operation == 'create';
                    })
                    ->disabled(),
                Textarea::make('curator_comment')
                    ->label(function ($record) {
                        if ($record->status == ReportStatus::PENDING_APPROVAL->value || $record->status == ReportStatus::PENDING_REJECTION->value) {
                            return 'Curator 1 Comment';
                        } elseif ($record->status == ReportStatus::APPROVED->value || $record->status == ReportStatus::REJECTED->value) {
                            return 'Curator 2 Comment';
                        }
                    })
                    ->hidden(function ($record) {
                        if ($record?->status == ReportStatus::PENDING_APPROVAL->value || $record?->status == ReportStatus::PENDING_REJECTION->value) {
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
                        return $get('report_category') !== ReportCategory::UPDATE->value;
                    }),
                Tabs::make('new_molecule_form')
                    ->tabs([
                        Tabs\Tab::make('molecule_info')
                            ->label('Molecule Information')
                            ->icon('heroicon-o-beaker')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        TextInput::make('canonical_smiles')
                                            ->label('Canonical SMILES')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($get('report_category') == ReportCategory::SUBMISSION->value && $state) {
                                                    $currentTitle = $get('title');
                                                    $set('title', $currentTitle.' '.$state);
                                                }
                                            })
                                            ->required()
                                            ->maxLength(1000)
                                            ->placeholder('Enter the canonical SMILES representation of the molecule')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'The canonical SMILES string that uniquely identifies the molecular structure')
                                            ->columnSpan(2),

                                        TextInput::make('reference_id')
                                            ->label('Reference ID')
                                            ->maxLength(255)
                                            ->placeholder('Enter a unique reference ID for this molecule')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'A unique identifier for referencing this molecule'),

                                        TextInput::make('name')
                                            ->label('Molecule Name')
                                            ->maxLength(255)
                                            ->placeholder('Enter the name of the molecule')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'The primary name or systematic name of the molecule'),

                                        TextInput::make('mol_filename')
                                            ->label('Molecule Filename')
                                            ->maxLength(255)
                                            ->placeholder('Enter the filename for the molecule structure')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Name of the structure file if available'),

                                        TextInput::make('link')
                                            ->label('Link')
                                            ->url()
                                            ->maxLength(1000)
                                            ->placeholder('Enter any relevant URL')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Any additional URL reference for this molecule'),

                                        Textarea::make('structural_comments')
                                            ->label('Structural Comments')
                                            ->maxLength(1000)
                                            ->placeholder('Enter any comments about the molecular structure')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Additional notes or comments about the molecular structure'),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('source_relationships')
                            ->label('Source Relationships')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Repeater::make('references')
                                    ->label('References')
                                    ->schema([
                                        TextInput::make('doi')
                                            ->label('DOI')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Enter the DOI reference')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Digital Object Identifier (DOI) for the publication'),

                                        Repeater::make('organisms')
                                            ->label('Organisms')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Organism Name')
                                                    ->live(onBlur: true)
                                                    ->required(
                                                        fn (callable $get): bool => ! empty($get('parts'))
                                                    )
                                                    ->maxLength(255)
                                                    ->placeholder('Enter organism name')
                                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Scientific name of the organism'),

                                                TagsInput::make('parts')
                                                    ->label('Organism Parts')
                                                    ->placeholder('Add organism part')
                                                    ->live(onBlur: true)
                                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Parts of the organism where the molecule was found'),

                                                Repeater::make('locations')
                                                    ->label('Geographic Locations')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Location Name')
                                                            ->required(
                                                                fn (callable $get): bool => ! empty($get('ecosystems'))
                                                            )
                                                            ->live(onBlur: true)
                                                            ->maxLength(255)
                                                            ->placeholder('Enter location name')
                                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Name of the geographic location'),

                                                        TagsInput::make('ecosystems')
                                                            ->label('Ecosystems/Sublocations')
                                                            ->placeholder('Add ecosystem')
                                                            ->live(onBlur: true)
                                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Specific ecosystems or sublocations where the organism was found'),
                                                    ])
                                                    ->addActionLabel('Add Location')
                                                    ->minItems(1)
                                                    ->collapsible()
                                                    ->columns(2),
                                            ])
                                            ->addActionLabel('Add Organism')
                                            ->minItems(1)
                                            ->collapsible()
                                            ->columns(1),
                                    ])
                                    ->addActionLabel('Add Reference')
                                    ->minItems(1)
                                    ->collapsible()
                                    ->columns(1),
                            ]),
                    ])
                    ->hidden(fn (Get $get) => $get('report_category') !== ReportCategory::SUBMISSION->value),
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
                    ->hidden(fn (Get $get) => $get('report_category') == ReportCategory::SUBMISSION->value),
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
                Textarea::make('mol_ids')
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
                    })
                    ->rules([
                        'array',
                        fn ($state): Closure => function (Closure $fail) use ($state) {
                            foreach ($state as $tag) {
                                if (! DB::table('molecules')->where('identifier', $tag)->exists()) {
                                    $fail("The molecule identifier '{$tag}' is invalid.");
                                }
                            }
                        },
                    ]),
                Textarea::make('comment')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide your comments/observations on anything noteworthy in the Curation process.'),
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
                         '<span class="font-medium">'.$record->report_category.'</span>'
                    )),
                TextColumn::make('user.name')
                    ->label('Reported By'),
                // Curator assignment column
                Tables\Columns\TextColumn::make('assigned_curator')
                    ->label('Assigned To')
                    ->state(function (Report $record): ?string {
                        if ($record->status === ReportStatus::SUBMITTED->value) {
                            $curator = $record->curators()->wherePivot('curator_number', 1)->first();

                            return $curator ? $curator->name : '';
                        } elseif ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
                            $curator = $record->curators()->wherePivot('curator_number', 2)->first();

                            return $curator ? $curator->name : '';
                        } elseif ($record->status === ReportStatus::APPROVED->value || $record->status === ReportStatus::REJECTED->value) {
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
            ->recordUrl(fn (Report $record): string => 
                auth()->user()->can('update', $record) 
                    ? static::getUrl('edit', ['record' => $record->id])
                    : static::getUrl('view', ['record' => $record->id])
            )
            ->defaultSort('created_at', 'desc')
            ->filters([
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_curator')
                    ->label('')
                    ->size('xl')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Radio::make('curator')
                            ->label(function (Report $record) {
                                if ($record->status === ReportStatus::SUBMITTED->value) {
                                    return 'Assign Curator 1';
                                } elseif ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
                                    return 'Assign Curator 2';
                                }

                                return 'Assign Curator';
                            })
                            ->options(function (Report $record) {
                                // Get all users with curator roles
                                $curators = User::whereHas('roles')->pluck('name', 'id')->toArray();

                                // For pending reports, filter out the first curator to enforce four-eyes principle
                                if ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
                                    $curator1 = $record->curators()->wherePivot('curator_number', 1)->first();
                                    if ($curator1) {
                                        unset($curators[$curator1->id]);
                                    }
                                }

                                return $curators;
                            })
                            ->default(function (Report $record) {
                                // Pre-select current curator if assigned
                                if ($record->status === ReportStatus::SUBMITTED->value) {
                                    $curator = $record->curators()->wherePivot('curator_number', 1)->first();

                                    return $curator?->id;
                                } elseif ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
                                    $curator = $record->curators()->wherePivot('curator_number', 2)->first();

                                    return $curator?->id;
                                }

                                return null;
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, Report $record): void {
                        $curatorNumber = 1; // Default

                        if ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
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
                        if ($record->status === ReportStatus::SUBMITTED->value) {
                            return 'Assign Curator 1';
                        } elseif ($record->status === ReportStatus::PENDING_APPROVAL->value || $record->status === ReportStatus::PENDING_REJECTION->value) {
                            return 'Assign Curator 2';
                        }

                        return 'Assign Curator';
                    })
                    ->modalSubmitActionLabel('Assign')
                    ->hidden(function (Report $record): bool {
                        // Hide for non-curators or approved/rejected reports
                        return ! auth()->user()->roles()->exists() ||
                            $record->status === ReportStatus::APPROVED->value ||
                            $record->status === ReportStatus::REJECTED->value;
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
            RelationManagers\EntriesRelationManager::class,
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

        $approved_changes['mol_ids'] = $record['mol_ids'];
        if ($record['report_category'] === ReportCategory::UPDATE->value) {

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
        $status = '';
        $curator_number = '';

        if ($record['report_category'] === ReportCategory::SUBMISSION->value) {
            if ($record['status'] == ReportStatus::PENDING_APPROVAL->value || $record['status'] == ReportStatus::PENDING_REJECTION->value) {
                $molecule_data = $record['suggested_changes']['new_molecule_data'];

                // Create new entry
                $new_entry = new Entry;
                $new_entry->canonical_smiles = $molecule_data['canonical_smiles'];
                $new_entry->reference_id = $molecule_data['reference_id'] ?? '';
                $new_entry->name = $molecule_data['name'] ?? '';
                $new_entry->status = ReportStatus::SUBMITTED->value;
                $new_entry->submission_type = 'json';
                $new_entry->collection_id = 65; // Default collection ID

                // Add optional fields if provided (with blank defaults)
                $new_entry->link = $molecule_data['link'] ?? '';
                $new_entry->mol_filename = $molecule_data['mol_filename'] ?? '';
                $new_entry->structural_comments = $molecule_data['structural_comments'] ?? '';

                // Initialize relationship fields with empty strings by default
                $new_entry->doi = '';
                $new_entry->organism = '';
                $new_entry->organism_part = '';
                $new_entry->geo_location = '';
                $new_entry->location = '';

                // Process relationships data
                $allDois = [];
                $allOrganisms = [];
                $allParts = [];
                $allGeoLocations = [];
                $allEcosystems = [];

                if (! empty($molecule_data['references'])) {
                    // Process each reference
                    foreach ($molecule_data['references'] as $reference) {
                        $doi = $reference['doi'] ?? '';

                        // If no organisms, still create an entry with blank values to maintain structure
                        if (empty($reference['organisms'])) {
                            $allDois[] = $doi;
                            $allOrganisms[] = '';
                            $allParts[] = '';
                            $allGeoLocations[] = '';
                            $allEcosystems[] = '';

                            continue;
                        }

                        // Process each organism in the reference
                        foreach ($reference['organisms'] as $organism) {
                            // Add DOI and organism name (one-to-one relation)
                            $allDois[] = $doi;
                            $allOrganisms[] = $organism['name'] ?? '';

                            // Process parts for this organism
                            $orgParts = ! empty($organism['parts']) ? implode('|', $organism['parts']) : '';
                            $allParts[] = $orgParts;

                            // Process locations and ecosystems for this organism
                            $orgLocations = [];
                            $orgEcosystems = [];

                            if (empty($organism['locations'])) {
                                // No locations, add blanks but maintain structure
                                $allGeoLocations[] = '';
                                $allEcosystems[] = '';
                            } else {
                                // Process each location in the organism
                                foreach ($organism['locations'] as $location) {
                                    $orgLocations[] = $location['name'] ?? '';

                                    // Process ecosystems for this location
                                    $locEcosystems = ! empty($location['ecosystems']) ? implode(';', $location['ecosystems']) : '';
                                    $orgEcosystems[] = $locEcosystems;
                                }

                                // Format the geo locations and ecosystems with appropriate delimiters
                                $allGeoLocations[] = implode('|', $orgLocations);
                                $allEcosystems[] = implode('|', $orgEcosystems);
                            }
                        }
                    }

                    // Only set relationship fields if there's actual data
                    if (! empty($allDois)) {
                        $new_entry->doi = implode('##', $allDois);
                        $new_entry->organism = implode('##', $allOrganisms);
                        $new_entry->organism_part = implode('##', $allParts);
                        $new_entry->geo_location = implode('##', $allGeoLocations);
                        $new_entry->location = implode('##', $allEcosystems);
                    }
                }

                // Store the original JSON data in meta_data
                $new_entry->meta_data = $record['suggested_changes'];

                $new_entry->save();

                $record['comment'] = prepareComment($data['reason'] ?? '');

                // Associate the entry with the report
                $record->entries()->attach($new_entry->id);

                $status = ReportStatus::APPROVED->value;
                $curator_number = 2;
            } else {
                $status = ReportStatus::PENDING_APPROVAL->value;
                $curator_number = 1;
            }
            // $livewire->redirect(ReportResource::getUrl('view', ['record' => $record->id]));
        } elseif ($record['report_category'] === ReportCategory::UPDATE->value) {
            // This has to be here so as to log the audit changes properly between the 1st and 2nd approvals.
            self::$overall_changes = getOverallChanges(self::$approved_changes);

            // In case of Changes
            if ($record['status'] == ReportStatus::PENDING_APPROVAL->value || $record['status'] == ReportStatus::PENDING_REJECTION->value) {
                // Run SQL queries for the approved changes
                self::runSQLQueries($record);

                $suggested_changes = $record['suggested_changes'];
                $suggested_changes['curator']['approved_changes'] = self::$overall_changes;
                $record['suggested_changes'] = $suggested_changes;
                $record['comment'] = prepareComment($data['reason'] ?? '');
                $record['status'] = ReportStatus::APPROVED->value;
                $formData = copyChangesToCuratorJSON($record, $livewire->data);
                $suggested_changes['curator'] = $formData['suggested_changes']['curator'];
                $record['suggested_changes'] = $suggested_changes;

                $status = ReportStatus::APPROVED->value;
                $curator_number = 2;
            } else {
                $status = ReportStatus::PENDING_APPROVAL->value;
                $curator_number = 1;
            }
        } else {
            // In case of reporting a synthetic molecule, Deactivate Molecules
            if ($record['status'] == ReportStatus::PENDING_APPROVAL->value || $record['status'] == ReportStatus::PENDING_REJECTION->value) {
                if ($record['report_type'] == 'molecule') {
                    $molecule_ids = json_decode($record['mol_ids'], true);
                    if (! is_array($molecule_ids)) {
                        $molecule_ids = explode(',', $record['mol_ids']); // Fallback for compatibility
                    }
                    $molecules = Molecule::whereIn('identifier', $molecule_ids)->get();
                    foreach ($molecules as $mol) {
                        $mol->active = false;
                        $mol->status = 'REVOKED';
                        $mol->comment = prepareComment($data['reason'] ?? '');
                        $mol->save();
                    }
                }
                $status = ReportStatus::APPROVED->value;
                $curator_number = 2;
            } else {
                $status = ReportStatus::PENDING_APPROVAL->value;
                $curator_number = 1;
            }
        }

        // Check if curator is already assigned
        $pivot = ReportUser::where('report_id', $record->id)
            ->where('user_id', auth()->id())
            ->where('curator_number', $curator_number)
            ->first();

        // If not assigned yet, assign curator based on report status
        if (! $pivot) {
            if ($record['status'] == ReportStatus::SUBMITTED->value) {
                // For first approval, automatically assign current user as curator 1
                $record->curators()->wherePivot('curator_number', 1)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 1,
                    'status' => $status,
                    'comment' => $data['reason'] ?? '',
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            } elseif ($record['status'] == ReportStatus::PENDING_APPROVAL->value || $record['status'] == ReportStatus::PENDING_REJECTION->value) {
                // For second approval, automatically assign current user as curator 2
                $record->curators()->wherePivot('curator_number', 2)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 2,
                    'status' => $status,
                    'comment' => $data['reason'] ?? '',
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            }
        } else {
            // Update existing pivot record
            $pivot->status = $status;
            $pivot->comment = $data['reason'] ?? '';
            $pivot->save();
        }
        $record['status'] = $status;
        $record->save();
        $record->refresh();
        ReportStatusChanged::dispatch($record);

        // Show appropriate notification based on action
        if ($status == ReportStatus::PENDING_APPROVAL->value) {
            Notification::make()
                ->title('Report approved for first review')
                ->body('The report has been approved in first review and is now pending final approval.')
                ->success()
                ->send();
        } elseif ($status == ReportStatus::APPROVED->value) {
            Notification::make()
                ->title('Report fully approved')
                ->body('The report has been fully approved.')
                ->success()
                ->send();
        }
        $livewire->redirect(ReportResource::getUrl('index'));
    }

    public static function rejectReport(array $data, Report $record, $livewire): void
    {
        $status = '';
        $curator_number = '';
        if ($record['status'] == ReportStatus::PENDING_APPROVAL->value || $record['status'] == ReportStatus::PENDING_REJECTION->value) {
            $status = ReportStatus::REJECTED->value;
            $curator_number = 2;
        } else {
            $status = ReportStatus::PENDING_REJECTION->value;
            $curator_number = 1;
        }
        // Check if curator is already assigned
        $pivot = ReportUser::where('report_id', $record->id)
            ->where('user_id', auth()->id())
            ->where('curator_number', $curator_number)
            ->first();

        // If not assigned yet, assign curator based on report status
        if (! $pivot) {
            if ($record['status'] == ReportStatus::SUBMITTED->value) {
                // For first rejection, automatically assign current user as curator 1
                $record->curators()->wherePivot('curator_number', 1)->detach();
                $record->curators()->attach(auth()->id(), [
                    'curator_number' => 1,
                    'status' => $status,
                    'comment' => $data['reason'],
                ]);
                // ReportAssigned::dispatch($record, auth()->id());
            } elseif ($record['status'] == ReportStatus::PENDING_APPROVAL->value || $record['status'] == ReportStatus::PENDING_REJECTION->value) {
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
        if ($status == ReportStatus::PENDING_REJECTION->value) {
            Notification::make()
                ->title('Report rejected in first review')
                ->body('The report has been rejected in first review and is now pending final rejection.')
                ->warning()
                ->send();
        } elseif ($status == ReportStatus::REJECTED->value) {
            Notification::make()
                ->title('Report fully rejected')
                ->body('The report has been rejected.')
                ->danger()
                ->send();
        }

        $livewire->redirect(ReportResource::getUrl('index'));
    }

    public static function runSQLQueries(Report $record): void
    {
        DB::transaction(function () use ($record) {
            self::$overall_changes = getOverallChanges(self::$approved_changes);

            // Handle mol_ids properly whether it's a string, an array, or enum value
            $molecule_identifier = $record['mol_ids'];
            // If mol_ids is an array (from JSON column), get the first item
            if (is_array($molecule_identifier)) {
                $molecule_identifier = $molecule_identifier[0] ?? null;
            } elseif (is_string($molecule_identifier)) {
                // If it's a string, check if it's a JSON string
                $decoded = json_decode($molecule_identifier, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $molecule_identifier = $decoded[0] ?? null;
                }
            }

            // Check if 'molecule_id' is provided or use a default molecule for association/dissociation
            $molecule = Molecule::where('identifier', $molecule_identifier)->first();

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
