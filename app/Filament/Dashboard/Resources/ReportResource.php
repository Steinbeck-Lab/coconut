<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\Molecule;
use App\Models\Report;
use App\Models\User;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
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
                                ->hidden(function (Get $get, string $operation, ?Report $record) {
                                    return ! (auth()->user()->roles()->exists() && ($operation == 'view' || $operation == 'edit') && ($get('status') != 'approved' || $get('status') != 'rejected') && ($record['assigned_to'] == null || $record['assigned_to'] == auth()->id()));
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
                                ->hidden(function (Get $get, string $operation, ?Report $record) {
                                    return ! (auth()->user()->roles()->exists() && ($operation == 'view' || $operation == 'edit') && ($get('status') != 'approved' || $get('status') != 'rejected') && ($record['assigned_to'] == null || $record['assigned_to'] == auth()->id()));
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
                            Action::make('assign')
                                ->hidden(function (Get $get, string $operation, ?Report $record) {
                                    return ! (auth()->user()->roles()->exists() && ($operation == 'view' || $operation == 'edit') && ($get('status') != 'approved' || $get('status') != 'rejected'));
                                })
                                ->form([
                                    Radio::make('curator')
                                        ->label('Choose a curator')
                                        ->options(function () {
                                            return $users = User::whereHas('roles')->pluck('name', 'id');
                                        }),
                                ])
                                ->action(function (array $data, Report $record): void {
                                    $record['assigned_to'] = $data['curator'];
                                    $record->save();
                                    $record->refresh();
                                })
                                ->modalHeading('')
                                ->modalSubmitActionLabel('Assign')
                                ->iconButton()
                                ->icon('heroicon-m-user-group')
                                // ->badge(function (?Report $record) {
                                //     return $record->curator->name ?? null;
                                // })
                                ->extraAttributes([
                                    'class' => 'ml-1 mr-0',
                                ])
                                ->size('xl'),
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
                KeyValue::make('suggested_changes')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter the property (in the left column) and suggested change (in the right column)')
                    ->addActionLabel('Add property')
                    ->keyLabel('Property')
                    ->valueLabel('Suggested change')
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
                            ->modalHidden(fn (): bool => ! auth()->user()->roles()->exists()),
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
}
