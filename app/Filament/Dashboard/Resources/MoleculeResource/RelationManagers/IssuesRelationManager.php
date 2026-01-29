<?php

namespace App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IssuesRelationManager extends RelationManager
{
    protected static string $relationship = 'issues';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('comment')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('is_acive')
                    ->label('Active')
                    ->hidden(function (string $operation) {
                        return $operation == 'create';
                    }),
                Toggle::make('is_resolved')
                    ->label('Resolved')
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
                TextColumn::make('title'),
                TextColumn::make('comment'),
                TextColumn::make('is_active')
                    ->label('Active')
                    ->formatStateUsing(
                        function (string $state) {
                            return $state ? 'Yes' : 'No';
                        }
                    ),
                TextColumn::make('is_resolved')
                    ->label('Resolved')
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
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
                AttachAction::make()->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
