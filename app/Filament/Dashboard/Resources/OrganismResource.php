<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\OrganismResource\Pages;
use App\Filament\Dashboard\Resources\OrganismResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\OrganismResource\Widgets\OrganismStats;
use App\Models\Organism;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class OrganismResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $model = Organism::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        Group::make()
                            ->schema([
                                Section::make('')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->unique()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('iri')
                                            ->label('IRI')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('rank')
                                            ->maxLength(255),
                                    ]),
                            ])
                            ->columnSpan(1),

                        Group::make()
                            ->schema([
                                Section::make('Similar Organisms')
                                    ->schema([

                                    ]),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2),  // Defines the number of columns in the grid
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rank')->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                AdvancedFilter::make()
                    ->includeColumns(),
            ])
            ->actions([
                Tables\Actions\Action::make('iri')
                    ->label('IRI')
                    ->url(fn (Organism $record) => $record->iri ? urldecode($record->iri) : null, true)
                    ->color('info')
                    ->icon('heroicon-o-link'),
                // Tables\Actions\ViewAction::make(),
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
        $arr = [
            MoleculesRelationManager::class,
            AuditsRelationManager::class,
        ];

        return $arr;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganisms::route('/'),
            'create' => Pages\CreateOrganism::route('/create'),
            'edit' => Pages\EditOrganism::route('/{record}/edit'),
            // 'view' => Pages\ViewOrganism::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            OrganismStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::get('stats.organisms');
    }
}
