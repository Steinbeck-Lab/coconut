<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ReportResource\Pages;
use App\Filament\Dashboard\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Events\ReportStatusChanged;

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
                TextInput::make('title')
                    ->required(),
                TextArea::make('evidence'),
                TextInput::make('url'),
                Select::make('collections')
                    ->relationship('collections', 'title')
                    ->multiple()
                    ->preload(function(string $operation) {
                        if($operation === 'create') {
                            return true;
                        }
                    })
                    ->hidden(function (string $operation) {
                        if($operation != 'create') {
                            return true;
                        }
                    })
                    ->searchable(),
                Select::make('citations')
                    ->relationship('citations', 'title')
                    ->multiple()
                    ->preload(function(string $operation) {
                        if($operation === 'create') {
                            return true;
                        }
                    })
                    ->hidden(function (string $operation) {
                        if($operation != 'create') {
                            return true;
                        }
                    })
                    ->searchable(),
                // Select::make('molecules')
                //     ->relationship('molecules', 'identifier')
                //     ->multiple()
                //     ->preload()
                //     ->searchable(),
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
