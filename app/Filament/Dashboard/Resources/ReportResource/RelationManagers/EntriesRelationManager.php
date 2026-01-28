<?php

namespace App\Filament\Dashboard\Resources\ReportResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference_id')
                    ->required()
                    ->maxLength(255),
                TextInput::make('canonical_smiles')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('doi')
                    ->required()
                    ->maxLength(255),
                TextInput::make('link')
                    ->required()
                    ->maxLength(255),
                TextInput::make('organism')
                    ->required()
                    ->maxLength(255),
                TextInput::make('organism_part')
                    ->required()
                    ->maxLength(255),
                TextInput::make('mol_filename')
                    ->required()
                    ->maxLength(255),
                TextInput::make('molecular_formula')
                    ->required()
                    ->maxLength(255),
                Textarea::make('structural_comments')
                    ->required(),
                TextInput::make('geo_location')
                    ->required()
                    ->maxLength(255),
                TextInput::make('location')
                    ->required()
                    ->maxLength(255),
                Textarea::make('errors')
                    ->required(),
                TextInput::make('standardized_canonical_smiles')
                    ->required()
                    ->maxLength(255),
                TextInput::make('parent_canonical_smiles')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([

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
                            return config('services.cheminf.api_url').'depict/2D?smiles='.urlencode($record->parent_canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                        })
                            ->width(200)
                            ->height(200)
                            ->ring('5')
                            ->defaultImageUrl(url('/images/placeholder.png')),
                        ImageEntry::make('canonical_smiles')->state(function ($record) {
                            return config('services.cheminf.api_url').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                        })
                            ->width(200)
                            ->height(200)
                            ->ring('5')
                            ->defaultImageUrl(url('/images/placeholder.png')),
                        ImageEntry::make('standardized_canonical_smiles')->state(function ($record) {
                            return config('services.cheminf.api_url').'depict/2D?smiles='.urlencode($record->standardized_canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                        })
                            ->width(200)
                            ->height(200)
                            ->ring('5')
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
                        return config('services.cheminf.api_url').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                TextColumn::make('reference_id')->searchable(),
                TextColumn::make('status'),
            ])
            ->filters([
            ])
            ->headerActions([
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->paginated([10, 25, 50, 100])
            ->extremePaginationLinks();
    }
}
