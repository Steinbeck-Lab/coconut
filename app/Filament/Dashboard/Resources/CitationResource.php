<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\CitationResource\Pages;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\CollectionRelationManager;
use App\Filament\Dashboard\Resources\CitationResource\RelationManagers\MoleculeRelationManager;
use App\Models\Citation;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Closure;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ViewField;
use App\Livewire\ShowStatus;
use Filament\Forms\Components\Livewire;

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
                        ->default('hello'),
                        Livewire::make(ShowStatus::class,  function(Get $get) {
                            return ['status' => $get('failMessage')];
                        })->live(),
                        TextInput::make('doi')
                            ->label('DOI')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($set, $state) {
                                $set('failMessage', 'Fetching');
                                if(doiRegxMatch($state)) {
                                    $citationDetails = fetchDOICitation($state);
                                    if($citationDetails) {
                                        $set('title', $citationDetails['title']);
                                        $set('authors', $citationDetails['authors']);
                                        $set('citation_text', $citationDetails['citation_text']);
                                        $set('failMessage', 'Successful');
                                    } else {
                                        $set('failMessage', 'No citation found');
                                    }
                                } else {
                                    $set('failMessage', 'Invalid DOI');
                                }
                            })
                            ->required()
                            ->unique()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if ($get('failMessage')) {
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
                        
                        // ViewField::make('forms.loading'),
                        TextInput::make('title'),
                        TextInput::make('authors'),
                        TextArea::make('citation_text')
                            ->label('Citation text / URL'),
                ])->columns(1)
            ])
            ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->wrap()
                    ->description(fn (Citation $citation): string => $citation->authors.' ~ '.$citation->doi),
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
}
