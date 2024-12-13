<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class IssuesRelationManager extends RelationManager
{
    protected static string $relationship = 'issues';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('comment')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('is_active')
                    ->label('Active')
                    ->required()
                    ->maxLength(255)
                    ->hidden(function (string $operation) {
                        return $operation == 'create';
                    }),
                Forms\Components\TextInput::make('is_fixed')
                    ->label('Fixed')
                    ->required()
                    ->maxLength(255)
                    ->hidden(function (string $operation) {
                        return $operation == 'create';
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('comment'),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Active')
                    ->formatStateUsing(
                        function (string $state) {
                            return $state ? 'Yes' : 'No';
                        }
                    ),
                Tables\Columns\TextColumn::make('is_fixed')
                    ->label('Fixed')
                    ->formatStateUsing(
                        function (string $state) {
                            return $state ? 'Yes' : 'No';
                        }
                    ),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
                Tables\Actions\AttachAction::make()->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
