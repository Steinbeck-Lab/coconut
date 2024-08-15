<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\RelationManagers;

use App\Models\Molecule;
use App\Models\Organism;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class MoleculesRelationManager extends RelationManager
{
    protected static string $relationship = 'molecules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
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
                        return env('CM_API', 'https://api.cheminf.studio/latest/').'depict/2D?smiles='.urlencode($record->canonical_smiles).'&height=300&width=300&CIP=true&toolkit=cdk';
                    })
                    ->width(200)
                    ->height(200)
                    ->ring(5)
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('id')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('identifier')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')->searchable()
                    ->formatStateUsing(
                        fn (Molecule $molecule): HtmlString => new HtmlString("<strong>ID:</strong> {$molecule->id}<br><strong>Identifier:</strong> {$molecule->identifier}<br><strong>Name:</strong> {$molecule->name}")
                    )
                    ->description(fn (Molecule $molecule): string => $molecule->standard_inchi)
                    ->wrap(),
                Tables\Columns\TextColumn::make('synonyms')
                    ->searchable()
                    ->wrap()
                    ->lineClamp(6)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('properties.exact_molecular_weight')
                    ->label('Mol.Wt')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('properties.np_likeness')
                    ->label('NP Likeness')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AssociateAction::make()->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DissociateAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    BulkAction::make('Change Association')
                        ->form([
                            Forms\Components\Select::make('org_id')
                                ->label('Name')
                                ->options(function () {
                                    return Organism::where([
                                        ['name', 'ilike', '%'.$this->getOwnerRecord()->name.'%'],
                                        ['name', '<>', $this->getOwnerRecord()->name],
                                    ])->pluck('name', 'id');
                                })
                                ->live(onBlur: true)
                                ->required(),
                            Forms\Components\Select::make('part')
                                ->options(function (callable $get) {
                                    return DB::table('molecule_organism')->where([
                                        ['organism_id', $get('org_id')],
                                        ['organism_parts', '<>', null],
                                        ['organism_parts', '<>', ''],
                                    ])->pluck('organism_parts', 'id');
                                })
                                ->multiple()
                            // ->createOptionForm([
                            //     Forms\Components\TextInput::make('add_part')
                            //         ->required(),
                            // ])
                            // ->createOptionUsing(function (array $data, $livewire): int {
                            //     // dd($livewire->getSelectedTableRecords());
                            //     return DB::table('molecule_organism')->insert([
                            //         'organism_id' => $data['org_id'],
                            //         'molecule_id' => 2147483640,
                            //         'organism_parts' => $data['add_part'],
                            //     ]);
                            // })
                            ,

                        ])
                        ->action(function (array $data, Collection $records): void {
                            DB::transaction(function () use ($data, $records) {
                                foreach ($records as $record) {
                                    foreach ($data['part'] as $part) {
                                        $existing_record = $this->getOwnerRecord()->molecules()
                                            // ->wherePivot('molecule_id', $record->id)
                                            // ->wherePivot('organism_id', $data['org_id'])
                                            // ->wherePivot('organism_parts', $part)
                                            ->wherePivot('molecule_id', 260)
                                            ->wherePivot('organism_id', 892)
                                            ->wherePivot('organism_parts', 'Stem')
                                            ->first();
                                        if ($existing_record !== null) {
                                            dd($existing_record);
                                            $this->getOwnerRecord()->molecules()->attch($record->id, ['organism_parts' => $part]);
                                        } else {
                                            // $this->getOwnerRecord()->molecules()->syncWithPivotValues($record->id, ['organism_parts' => $part]);
                                        }
                                        // DB::table('molecule_organism')->insert([
                                        //     'organism_id' => $data['org_id'],
                                        //     'molecule_id' => $record->id,
                                        //     'organism_parts' => $part,
                                        // ]);
                                    }
                                }
                            });

                            // $this->getOwnerRecord()->molecules()->detach($records->pluck('id'));
                        }),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
