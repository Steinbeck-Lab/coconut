<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\RelationManagers;

use App\Filament\Dashboard\Imports\EntryImporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('canonical_smiles')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('doi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('link')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('organism')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('organism_part')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mol_filename')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('molecular_formula')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('structural_comments')
                    ->required(),
                Forms\Components\TextInput::make('geo_location')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('location')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('errors')
                    ->required(),
                Forms\Components\TextInput::make('standardized_canonical_smiles')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('parent_canonical_smiles')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Section::make()
                    ->schema([
                        TextEntry::make('reference_id'),
                        TextEntry::make('name'),
                        TextEntry::make('doi'),
                        TextEntry::make('link'),
                        TextEntry::make('organism'),
                        TextEntry::make('organism_part'),
                        TextEntry::make('molecular_formula'),
                        TextEntry::make('structural_comments'),
                        TextEntry::make('geo_location'),
                        TextEntry::make('location'),
                        TextEntry::make('errors'),
                    ]),
                Section::make()
                    ->columns([
                        'sm' => 3,
                        'xl' => 3,
                        '2xl' => 3,
                    ])
                    ->schema([
                        ImageEntry::make('parent_canonical_smiles')->state(function ($record) {
                            return env('CM_PUBLIC_API', 'https://api.cheminf.studio/latest/').'depict/2D?smiles='.urlencode($record->parent_canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                        })
                            ->width(200)
                            ->height(200)
                            ->ring(5)
                            ->defaultImageUrl(url('/images/placeholder.png')),
                        ImageEntry::make('canonical_smiles')->state(function ($record) {
                            return env('CM_PUBLIC_API', 'https://api.cheminf.studio/latest/').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                        })
                            ->width(200)
                            ->height(200)
                            ->ring(5)
                            ->defaultImageUrl(url('/images/placeholder.png')),
                        ImageEntry::make('standardized_canonical_smiles')->state(function ($record) {
                            return env('CM_PUBLIC_API', 'https://api.cheminf.studio/latest/').'depict/2D?smiles='.urlencode($record->standardized_canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                        })
                            ->width(200)
                            ->height(200)
                            ->ring(5)
                            ->defaultImageUrl(url('/images/placeholder.png')),
                        // ...
                    ]),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('identifier')
            ->columns([
                ImageColumn::make('structure')->square()
                    ->label('Structure')
                    ->state(function ($record) {
                        return env('CM_PUBLIC_API', 'https://api.cheminf.studio/latest/').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('reference_id')->searchable(),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'PASSED' => 'PASSED',
                        'SUBMITTED' => 'SUBMITTED',
                        'PROCESSING' => 'PROCESSING',
                        'INREVIEW' => 'INREVIEW',
                        'PASSED' => 'PASSED',
                        'REJECTED' => 'REJECTED',
                    ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(EntryImporter::class)
                    ->options([
                        'collection_id' => $this->ownerRecord->id,
                    ]),
                Action::make('process')
                    ->hidden(function () {
                        return $this->ownerRecord->entries()->where('status', 'SUBMITTED')->count() < 1;
                    })
                    ->action(function () {
                        Artisan::call('coconut:entries-process');
                    }),
                Action::make('publish')
                    ->hidden(function () {
                        return $this->ownerRecord->molecules()->where('status', 'DRAFT')->count() < 1;
                    })
                    ->action(function () {
                        $this->ownerRecord->status = 'PUBLISHED';
                        $this->ownerRecord->is_public = true;
                        $this->ownerRecord->molecules()->where('status', 'DRAFT')->update(['status' => 'APPROVED']);
                        $this->ownerRecord->save();
                    }),
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->paginated([10, 25, 50, 100])
            ->extremePaginationLinks();
    }
}
