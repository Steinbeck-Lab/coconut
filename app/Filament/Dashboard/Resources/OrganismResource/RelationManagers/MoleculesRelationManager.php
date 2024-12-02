<?php

namespace App\Filament\Dashboard\Resources\OrganismResource\RelationManagers;

use App\Models\Molecule;
use App\Models\Organism;
use App\Models\SampleLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('identifier')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('identifier')
                    ->label('Details')
                    ->formatStateUsing(
                        function (Molecule $molecule, $record): HtmlString {
                            $sample_locations = $record->sampleLocations()->where('organism_id', $this->getOwnerRecord()->id)->pluck('name')->implode(', ');

                            return new HtmlString(
                                "<strong>ID:</strong> {$molecule->id}<br>
                                                    <strong>Identifier:</strong> {$molecule->identifier}<br>
                                                    <strong>Name:</strong> {$molecule->name}<br>
                                                    <strong>Sample Locations:</strong> {$sample_locations}"
                            );
                        }
                    )
                    ->description(fn (Molecule $molecule): string => $molecule->standard_inchi)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->wrap()
                    ->lineClamp(6)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\AttachAction::make()->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    BulkAction::make('Change Association')
                        ->form([
                            Forms\Components\Select::make('org_id')
                                ->label('Name')
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Organism::query()
                                        ->where(function (Builder $builder) use ($search) {
                                            $searchString = "%$search%";
                                            $builder->where('name', 'ilike', $searchString);
                                        })
                                        ->limit(10)
                                        ->get()
                                        ->mapWithKeys(function (Organism $organism) {
                                            return [$organism->id => $organism->name];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->live(onBlur: true)
                                ->required(),
                            Forms\Components\Select::make('locations')
                                ->relationship('sampleLocations', 'name')
                                ->getSearchResultsUsing(function (string $search, $get): array {
                                    return SampleLocation::query()
                                        ->where(function (Builder $builder) use ($search, $get) {
                                            $searchString = "%$search%";
                                            $builder->where('name', 'ilike', $searchString)
                                                ->where('organism_id', '=', $get('org_id'));
                                        })
                                        ->limit(10)
                                        ->get()
                                        ->mapWithKeys(function (SampleLocation $location) {
                                            return [$location->id => $location->name];
                                        })
                                        ->toArray();
                                })
                                ->multiple()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                    Forms\Components\TextInput::make('iri')
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data, $livewire): int {
                                    $slug = Str::slug($data['name']);
                                    $location_exists = SampleLocation::where('slug', $slug)->where('organism_id', $livewire->mountedTableBulkActionData['org_id'])->first();
                                    if ($location_exists) {
                                        return $location_exists->id;
                                    }

                                    return SampleLocation::create([
                                        'name' => str()->ucfirst(str()->trim($data['name'])),
                                        'slug' => $slug,
                                        'iri' => $data['iri'],
                                        'organism_id' => $livewire->mountedTableBulkActionData['org_id'],
                                    ])->getKey();
                                }),
                            Forms\Components\Repeater::make('molecule_locations')
                                ->schema([
                                    Forms\Components\TextInput::make('id'),
                                    Forms\Components\TextInput::make('name'),
                                    Forms\Components\Select::make('sampleLocations')
                                        ->relationship('sampleLocations', 'name')
                                        ->multiple()
                                        ->preload(),
                                ])
                                ->default(function ($livewire, Collection $filtered) {
                                    $records = $livewire->getSelectedTableRecords();
                                    foreach ($records as $record) {
                                        $record->sampleLocations = $record->sampleLocations()->where('organism_id', $this->getOwnerRecord()->id)->get();
                                        if ($record->sampleLocations->count() > 1) {
                                            $filtered->push($record);
                                        }
                                    }

                                    return $filtered->map(function ($record) {
                                        $location_ids = [];
                                        foreach ($record->sampleLocations as $location) {
                                            array_push($location_ids, $location->id);
                                        }

                                        return [
                                            'sampleLocations' => $location_ids,
                                            'name' => $record->name,
                                            'id' => $record->id,
                                        ];
                                    })->toArray();
                                })
                                ->columns(3)
                                ->reorderable(false)
                                ->addable(false)
                                ->deletable(false),
                        ])
                        ->action(function (array $data, Collection $records, $livewire): void {
                            DB::transaction(function () use ($data, $records, $livewire) {
                                DB::beginTransaction();
                                try {
                                    $current_sample_locations = [];
                                    $current_sample_locations_subset = [];
                                    $new_sample_locations = [];
                                    $molecule_with_one_location = [];
                                    $molecules_ids_with_multiple_locations = [];
                                    $molecues_with_muliple_locations = $livewire->mountedTableBulkActionData['molecule_locations'];

                                    // Extract ids of all selected molecules
                                    $moleculeIds = $records->pluck('id')->toArray();

                                    // Extract ids of molecules with multiple locations
                                    foreach ($molecues_with_muliple_locations as $molecule) {
                                        array_push($molecules_ids_with_multiple_locations, $molecule['id']);
                                    }

                                    // Extract ids of molecules with only one location
                                    $molecule_ids_with_one_location = array_diff($moleculeIds, $molecules_ids_with_multiple_locations);

                                    // Get the NEW organism
                                    $newOrganism = Organism::findOrFail($data['org_id']);

                                    // Get the NEW sample locations
                                    $new_form_sample_locations = $livewire->mountedTableBulkActionData['locations'];
                                    if ($new_form_sample_locations) {
                                        $new_sample_locations = SampleLocation::findOrFail($new_form_sample_locations);
                                    }

                                    // Get the CURRENT organism
                                    $currentOrganism = $this->getOwnerRecord();

                                    // Handling Molecules with only ONE location
                                    // Detach Molecules with only ONE location from CURRENT Organism
                                    if ($molecule_ids_with_one_location) {
                                        $molecule_with_one_location = $records->whereIn('id', $molecule_ids_with_one_location);
                                        $currentOrganism->auditDetach('molecules', $molecule_ids_with_one_location);

                                        // Detach molecules with only ONE location from CURRENT Sample Locations
                                        foreach ($molecule_with_one_location as $record) {
                                            $current_sample_locations = $record->sampleLocations()->where('organism_id', $this->getOwnerRecord()->id)->get();
                                            foreach ($current_sample_locations as $location) {
                                                $location->auditDetach('molecules', $record->id);
                                            }
                                        }
                                    }

                                    // Handling Molecules with MULTIPLE locations
                                    // Detach molecules from Sample Locations
                                    foreach ($molecues_with_muliple_locations as $molecule) {
                                        $current_sample_locations_subset = SampleLocation::findOrFail($molecule['sampleLocations']);
                                        $current_sample_locations_multiple = $records->where('id', $molecule['id'])[0]->sampleLocations()->where('organism_id', $this->getOwnerRecord()->id)->get();
                                        if ($current_sample_locations_multiple->pluck('id') == $current_sample_locations_subset->pluck('id')) {
                                            $currentOrganism->auditDetach('molecules', $molecule['id']);
                                        }
                                        foreach ($current_sample_locations_subset as $location) {
                                            $location->auditDetach('molecules', $molecule['id']);
                                            customAuditLog('re-assign', [$records->find($molecule['id'])], 'sampleLocations', $current_sample_locations_subset, $new_sample_locations);
                                        }
                                    }

                                    foreach ($new_sample_locations as $location) {
                                        $location->auditSyncWithoutDetaching('molecules', $moleculeIds);
                                        // customAuditLog('cust_sync', $records, 'sampleLocations', [], $location);
                                    }
                                    customAuditLog('re-assign', $molecule_with_one_location, 'sampleLocations', $current_sample_locations, $new_sample_locations);

                                    $newOrganism->auditSyncWithoutDetaching('molecules', $moleculeIds);
                                    customAuditLog('re-assign', $records, 'organisms', $currentOrganism, $newOrganism);

                                    $currentOrganism->refresh();
                                    $newOrganism->refresh();

                                    $currentOrganism->molecule_count = $currentOrganism->molecules()->count();
                                    $currentOrganism->save();
                                    $newOrganism->molecule_count = $newOrganism->molecules()->count();
                                    $newOrganism->save();

                                    DB::commit();
                                } catch (\Exception $e) {
                                    // Rollback the transaction in case of any error
                                    DB::rollBack();
                                    throw $e; // Optionally rethrow the exception
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
