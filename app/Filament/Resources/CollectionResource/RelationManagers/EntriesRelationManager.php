<?php

namespace App\Filament\Resources\CollectionResource\RelationManagers;

use App\Filament\Imports\EntryImporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('identifier')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('canonical_smiles')
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
                Forms\Components\TextInput::make('molecular_formula')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextArea::make('structural_comments')
                    ->required(),
                Forms\Components\TextArea::make('errors')
                    ->required(),
                Forms\Components\TextInput::make('standardized_canonical_smiles')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('parent_canonical_smiles')
                    ->required()
                    ->maxLength(255),
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
                        return 'https://api.naturalproducts.net/v1/depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=false&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('identifier'),
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
