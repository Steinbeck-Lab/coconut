<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\CitationResource\Pages;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\CollectionRelationManager;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\MoleculeRelationManager;
use App\Models\Citation;
use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CitationResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?string $model = Citation::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('failMessage')
                            ->default('')
                            ->hidden()
                            ->disabled(),
                        TextInput::make('doi')
                            ->label('DOI')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state) {
                                if (doiRegxMatch($state)) {
                                    $citationDetails = fetchDOICitation($state);
                                    if ($citationDetails) {
                                        $set('title', $citationDetails['title']);
                                        $set('authors', $citationDetails['authors']);
                                        $set('citation_text', $citationDetails['citation_text']);
                                        $set('failMessage', 'Success');
                                    } else {
                                        $set('failMessage', 'No citation found. Please fill in the details manually');
                                    }
                                } else {
                                    $set('failMessage', 'Invalid DOI');
                                }
                            })
                            ->helperText(function ($get) {

                                if ($get('failMessage') == 'Fetching') {
                                    return new HtmlString('<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-dark inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg> ');
                                } elseif ($get('failMessage') != 'Success') {
                                    return new HtmlString('<span style="color:red">'.$get('failMessage').'</span>');
                                } else {
                                    return null;
                                }
                            })
                            ->required()
                            ->unique()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('failMessage') != 'No citation found. Please fill in the details manually') {
                                        $fail($get('failMessage'));
                                    }
                                },
                            ])
                            ->validationMessages([
                                'unique' => 'The DOI already exists.',
                            ]),
                    ]),

                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->disabled(function ($get, string $operation) {
                                if ($operation = 'edit' || $get('failMessage') == 'No citation found. Please fill in the details manually') {
                                    return false;
                                } else {
                                    return true;
                                }
                            }),
                        TextInput::make('authors')
                            ->disabled(function ($get, string $operation) {
                                if ($operation = 'edit' || $get('failMessage') == 'No citation found. Please fill in the details manually') {
                                    return false;
                                } else {
                                    return true;
                                }
                            }),
                        Textarea::make('citation_text')
                            ->label('Citation text / URL')
                            ->disabled(function ($get, string $operation) {
                                if ($operation = 'edit' || $get('failMessage') == 'No citation found. Please fill in the details manually') {
                                    return false;
                                } else {
                                    return true;
                                }
                            }),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->wrap()
                    ->description(fn (Citation $citation): string => $citation->authors.' ~ '.$citation->doi)
                    ->searchable(),
                TextColumn::make('doi')->wrap()
                    ->label('DOI')
                    ->searchable(),
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
            CollectionRelationManager::class,
            MoleculeRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCitations::route('/'),
            'create' => Pages\CreateCitation::route('/create'),
            'edit' => Pages\EditCitation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::get('stats.citations');
    }
}
