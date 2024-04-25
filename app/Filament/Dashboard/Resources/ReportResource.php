<?php

namespace App\Filament\Dashboard\Resources;

use App\Events\ReportStatusChanged;
use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Citation;
use App\Models\Molecule;
use App\Models\Report;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
                Select::make('choice')
                    ->label('You want to report:')
                    ->live()
                    ->options([
                        'molecule' => 'Molecule',
                        'citation' => 'Citation',
                        'collection' => 'Collection',
                    ])
                    ->hidden(function (string $operation) {
                        if ($operation == 'create' && (! request()->has('collection_uuid') && ! request()->has('citation_id') && ! request()->has('compound_id'))) {
                            return false;
                        } else {
                            return true;
                        }
                    }),
                TextInput::make('title')
                    ->required(),
                TextArea::make('evidence'),
                TextInput::make('url'),
                Select::make('collections')
                    ->relationship('collections', 'title')
                    ->multiple()
                    ->preload()
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation == 'edit' || $operation == 'view') {
                            if ($get('collections') == []) {
                                return true;
                            }
                        } elseif (! request()->has('collection_uuid') && $get('choice') != 'collection') {
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
                    ->relationship('citations', 'title')
                    ->options(function () {
                        return Citation::whereNotNull('title')->pluck('title', 'id');
                    })
                    ->multiple()
                    // ->preload()
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation == 'edit' || $operation == 'view') {
                            if ($get('citations') == []) {
                                return true;
                            }
                        } elseif (! request()->has('citation_id') && $get('choice') != 'citation') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    })
                    ->searchable(),
                // Select::make('molecules')
                //     ->relationship('molecules', 'identifier')
                //     ->options(function () {
                //         return Molecule::select('id', 'identifier')->whereNotNull('identifier')->get();
                //     })
                //     ->multiple()
                //     ->hidden(function (Get $get) {
                //         if(!request()->has('compound_id') && $get('choice') != 'molecule') {
                //             return true;
                //         }
                //         else {
                //             return false;
                //         }
                //     }),
                //     ->searchable(),
                TextInput::make('mol_id_csv')
                    ->label('Molecules')
                    ->placeholder('Enter the Identifiers separated by commas')
                    ->hidden(function (Get $get, string $operation) {
                        if ($operation == 'edit' || $operation == 'view') {
                            if (is_null($get('mol_id_csv'))) {
                                return true;
                            }
                        } elseif (! request()->has('compound_id') && $get('choice') != 'molecule') {
                            return true;
                        }
                    })
                    ->disabled(function (string $operation) {
                        if ($operation == 'edit') {
                            return true;
                        }
                    }),
                SpatieTagsInput::make('tags')
                    ->splitKeys(['Tab', ','])
                    ->type('reports'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->hidden(function () {
                        return ! auth()->user()->hasRole('curator');
                    })
                    ->afterStateUpdated(function (?Report $record, ?string $state, ?string $old) {
                        // dd($record, $state, $record->status, $old);
                        ReportStatusChanged::dispatch($record, $state, $old);
                    }),
                TextArea::make('comment')
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
                // TextColumn::make('evidence')->words(10),
                TextColumn::make('url')
                    ->url(fn (Report $record): string => $record->url)
                    ->openUrlInNewTab(),
                TextColumn::make('status')
                    ->badge()
                    // Text column for status with badge and color based on status
                    ->color(function (Report $record) {
                        return match ($record->status) {
                            'pending' => 'info',
                            'approved' => 'success',
                            'rejected' => 'danger',
                        };
                    }),
                TextColumn::make('comment')->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
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
        return [
            RelationManagers\MoleculesRelationManager::class,
            RelationManagers\CollectionsRelationManager::class,
            RelationManagers\CitationsRelationManager::class,
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
