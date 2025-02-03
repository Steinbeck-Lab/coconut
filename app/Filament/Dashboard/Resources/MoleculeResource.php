<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\MoleculeResource\Pages;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\CitationsRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\CollectionsRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\GeoLocationRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\IssuesRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\MoleculesRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\OrganismsRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\PropertiesRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\RelationManagers\RelatedRelationManager;
use App\Filament\Dashboard\Resources\MoleculeResource\Widgets\MoleculeStats;
use App\Models\Molecule;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class MoleculeResource extends Resource
{
    protected static ?string $navigationGroup = 'Data';

    protected static ?string $model = Molecule::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    public static function customActionMethod()
    {
        // Custom logic here
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('identifier'),
                TextInput::make('iupac_name')
                    ->label('IUPAC Name'),
                TextInput::make('standard_inchi')
                    ->label('Standard InChI'),
                TextInput::make('standard_inchi_key')
                    ->label('Standard InChI Key'),
                TextInput::make('canonical_smiles')
                    ->label('Canonical SMILES'),
                TagsInput::make('synonyms')
                    ->placeholder('New Synonym')
                    ->disabled(function ($operation) {
                        return $operation == 'view';
                    }),
                TagsInput::make('cas')
                    ->placeholder('New CAS')
                    ->label('CAS')
                    ->disabled(function ($operation) {
                        return $operation == 'view';
                    }),
                TextInput::make('murcko_framework')
                    ->label('Murcko Framework'),
            ]);
    }

    public static function table(Table $table): Table
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
                AdvancedFilter::make()
                    ->includeColumns(),
                Filter::make('structure')
                    ->form([
                        Select::make('type')
                            ->options([
                                'substructure' => 'Sub Structure',
                                'exact' => 'Exact',
                                'similarity' => 'Similarity',
                            ]),
                        TextInput::make('smiles'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['type'] && $data['type'] == 'substructure' && $data['smiles']) {
                            $statement = "SELECT id, m, 
                                tanimoto_sml(morganbv_fp(mol_from_smiles('{$data['smiles']}')::mol), morganbv_fp(m::mol)) AS similarity 
                            FROM mols 
                            WHERE m@> mol_from_smiles('{$data['smiles']}')::mol 
                            ORDER BY similarity DESC";

                            $expression = DB::raw($statement);

                            $string = $expression->getValue(DB::connection()->getQueryGrammar());

                            $hits = DB::select($string);

                            $ids_array = collect($hits)->pluck('id')->toArray();

                            if (! empty($ids_array)) {
                                $query->whereIn('id', $ids_array);
                            }
                        }

                        return $query;
                    })->indicateUsing(function (array $data): ?string {
                        if (! $data['type'] && ! $data['smiles']) {
                            return null;
                        }

                        return $data['type'].':'.$data['smiles'];
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Action::make('report')
                        ->action(function (Molecule $record) {
                            Redirect::to(ReportResource::getUrl('create').'?compound_id='.$record->identifier);
                        }),
                    Action::make('moleculeActivateDeactivate')
                        ->label(function (Molecule $record) {
                            return $record['active'] ? 'Deactivate' : 'Activate';
                        })
                        ->hidden(function () {
                            return ! auth()->user()->roles()->exists();
                        })
                        ->form([
                            Textarea::make('reason')
                                ->required(function (Molecule $record) {
                                    return $record['active'];
                                }),
                        ])
                        ->action(function (array $data, Molecule $record): void {
                            self::changeMoleculeStatus($record, $data['reason']);
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('Active Status Change')
                        ->form([
                            Textarea::make('reason')
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                self::changeMoleculeStatus($record, $data['reason']);
                            }
                        })
                        // ->modalHidden(function (Molecule $record) {
                        //     return !$record['active'];
                        // })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('Report Molecules')
                        ->action(function (array $data, Collection $records): void {
                            Redirect::to(ReportResource::getUrl('create').'?compound_id='.implode(',', $records->pluck('identifier')->toArray()));
                        })
                        ->deselectRecordsAfterCompletion(),
                    ExportBulkAction::make()->exports([
                        ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::XLSX)->label('XLSX')->queue(),
                        ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::CSV)->label('CSV')->queue(),
                        // ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::TSV)->label('TSV')->queue(),
                        ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::ODS)->label('ODS')->queue(),
                        ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::XLS)->label('XLS')->queue(),
                        ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::HTML)->label('HTML')->queue(),
                        ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::MPDF)->label('MPDF')->queue(),
                        // ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::DOMPDF)->label('DOMPDF')->queue(),
                        // ExcelExport::make()->fromTable()->withWriterType(\Maatwebsite\Excel\Excel::TCPDF)->label('TCPDF')->queue(),
                    ])
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PropertiesRelationManager::class,
            CollectionsRelationManager::class,
            CitationsRelationManager::class,
            MoleculesRelationManager::class,
            RelatedRelationManager::class,
            GeoLocationRelationManager::class,
            OrganismsRelationManager::class,
            AuditsRelationManager::class,
            IssuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMolecules::route('/'),
            'create' => Pages\CreateMolecule::route('/create'),
            'edit' => Pages\EditMolecule::route('/{record}/edit'),
            'view' => Pages\ViewMolecule::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            MoleculeStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return Cache::flexible('stats.molecules', [172800, 259200], function () {
            return DB::table('molecules')->selectRaw('count(*)')->whereRaw('active=true and NOT (is_parent=true AND has_variants=true)')->get()[0]->count;
        });
    }

    public static function changeMoleculeStatus($record, $reason)
    {
        $record->active = ! $record->active;
        $record->active ? $record->status = 'APPROVED' : $record->status = 'REVOKED';

        $record->comment = prepareComment($reason);

        $record->save();
    }
}
