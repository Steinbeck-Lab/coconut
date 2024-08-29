<?php

namespace App\Filament\Dashboard\Resources;

use App\Events\ReportStatusChanged;
use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\Molecule;
use App\Models\Report;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maartenpaauw\Filament\ModelStates\StateSelectColumn;
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
                Toggle::make('is_change')
                    ->live()
                    ->label(function ($state) {
                        if ($state == true) {
                            return 'Request changes to data';
                        } else {
                            return 'Report false data';
                        }
                    }),
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
                    ->addActionLabel('Add property')
                    ->keyLabel('Property')
                    ->valueLabel('Suggested change')
                    ->hidden(function (Get $get) {
                        return ! $get('is_change');
                    }),
                TextInput::make('url')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide a link to the webpage that supports your claims.')
                    ->label('URL'),
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
                        if ($operation == 'edit' || $operation == 'view') {
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
                        if ($operation == 'edit' || $operation == 'view') {
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
                        if ($operation == 'edit' || $operation == 'view') {
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
                        if ($operation == 'edit' || $operation == 'view') {
                            if (is_null($get('mol_id_csv'))) {
                                return true;
                            }
                        } elseif (! request()->has('compound_id') && $get('report_type') != 'molecule') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    }),
                SpatieTagsInput::make('tags')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide comma separated search terms that would help in finding your report when searched.')
                    ->splitKeys(['Tab', ','])
                    ->type('reports'),
                // Select::make('status')
                //     ->options([
                //         'pending' => 'Pending',
                //         'approved' => 'Approved',
                //         'rejected' => 'Rejected',
                //     ])
                //     ->hidden(function () {
                //         return ! auth()->user()->hasRole('curator');
                //     })
                //     ->afterStateUpdated(function (?Report $record, ?string $state, ?string $old) {
                //         ReportStatusChanged::dispatch($record, $state, $old);
                //     }),
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
                    ->description(fn (Report $record): string => Str::of($record->evidence)->words(10)),
                TextColumn::make('url')
                    ->url(fn (Report $record) => $record->url)
                    ->openUrlInNewTab(),
                // StateSelectColumn::make('status'),

                SelectColumn::make('status')
                    ->selectablePlaceholder(false)
                    ->options(function ($state) {
                        if ($state == 'draft') {
                            return [
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                            ];
                        } elseif ($state != 'draft') {
                            return [
                                'draft' => 'Draft',
                                'submitted' => 'Submitted',
                                'processing' => 'Processing',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ];
                        }
                    })
                    ->disabled(function ($state) {
                        if (($state != 'draft' && ! auth()->user()->roles()->exists()) || $state == 'processing' || $state == 'approved' || $state == 'rejected') {
                            return true;
                        }
                    }),
                TextColumn::make('comment')->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        return $record->status == 'draft';
                    }),
                Tables\Actions\Action::make('approve')
                    ->hidden(function (Report $record) {
                        return ! auth()->user()->roles()->exists() || $record['status'] == 'draft' || $record['status'] == 'rejected' || $record['status'] == 'approved';
                    })
                    ->form([
                        Textarea::make('reason'),
                    ])
                    ->action(function (array $data, Report $record, Molecule $molecule): void {

                        $record['status'] = 'approved';
                        $record['comment'] = $data['reason'];
                        $record->save();

                        if ($molecule['mol_id_csv']) {
                            $molecule_ids = explode(',', $molecule['mol_id_csv']);
                            $molecule = Molecule::whereIn('id', $molecule_ids)->get();
                            foreach ($molecule as $mol) {
                                $mol->active = false;
                                $mol->save();
                            }
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->hidden(function (Report $record) {
                        return ! auth()->user()->roles()->exists() || $record['status'] == 'draft' || $record['status'] == 'rejected' || $record['status'] == 'approved';
                    })
                    ->form([
                        Textarea::make('reason'),

                    ])
                    ->action(function (array $data, Report $record): void {

                        $record['status'] = 'rejected';
                        $record['comment'] = $data['reason'];
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
