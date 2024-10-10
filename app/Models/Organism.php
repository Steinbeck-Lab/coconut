<?php

namespace App\Models;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Organism extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'iri',
        'rank',
        'molecule_count',
        'slug',
    ];

    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class)->withTimestamps();
    }

    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    public function sampleLocations(): HasMany
    {
        return $this->hasMany(SampleLocation::class);
    }

    public function getIriAttribute($value)
    {
        return urldecode($value);
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->unique(Organism::class, 'name')
                ->maxLength(255)
            // ->suffixAction(
            //     Action::make('infoFromSources')
            //         ->icon('heroicon-m-clipboard')
            //         // ->fillForm(function ($record, callable $get): array {
            //         //     $entered_name = $get('name');
            //         //     $name = ucfirst(trim($entered_name));
            //         //     $data = null;
            //         //     $iri = null;
            //         //     $organism = null;
            //         //     $rank = null;

            //         //     if ($name && $name != '') {
            //         //         $data = Self::getOLSIRI($name, 'species');
            //         //         if ($data) {
            //         //             Self::updateOrganismModel($name, $data, $record, 'species');
            //         //             Self::info("Mapped and updated: $name");
            //         //         } else {
            //         //             $data = Self::getOLSIRI(explode(' ', $name)[0], 'genus');
            //         //             if ($data) {
            //         //                 Self::updateOrganismModel($name, $data, $record, 'genus');
            //         //                 Self::info("Mapped and updated: $name");
            //         //             } else {
            //         //                 $data = Self::getOLSIRI(explode(' ', $name)[0], 'family');
            //         //                 if ($data) {
            //         //                     Self::updateOrganismModel($name, $data, $record, 'genus');
            //         //                     Self::info("Mapped and updated: $name");
            //         //                 } else {
            //         //                     [$name, $iri, $organism, $rank] = Self::getGNFMatches($name, $record);
            //         //                 }
            //         //             }
            //         //         }
            //         //     }
            //         //     return [
            //         //         'name' => $name,
            //         //         'iri' => $iri,
            //         //         'rank' => $rank,
            //         //     ];
            //         // })
            //         // ->form([
            //         //     Forms\Components\TextInput::make('name')->readOnly(),
            //         //     Forms\Components\TextInput::make('iri')->readOnly(),
            //         //     Forms\Components\TextInput::make('rank')->readOnly(),
            //         // ])
            //         // ->action(fn ( $record) => $record->advance())
            //         ->modalContent(function ($record, $get): View {
            //             $name = ucfirst(trim($get('name')));
            //             $data = null;
            //             // $iri = null;
            //             // $organism = null;
            //             // $rank = null;

            //             if ($name && $name != '') {
            //                 $data = self::getOLSIRI($name, 'species');
            //                 // if ($data) {
            //                 //     Self::updateOrganismModel($name, $data, $record, 'species');
            //                 // } else {
            //                 //     $data = Self::getOLSIRI(explode(' ', $name)[0], 'genus');
            //                 //     if ($data) {
            //                 //         Self::updateOrganismModel($name, $data, $record, 'genus');
            //                 //     } else {
            //                 //         $data = Self::getOLSIRI(explode(' ', $name)[0], 'family');
            //                 //         if ($data) {
            //                 //             Self::updateOrganismModel($name, $data, $record, 'genus');
            //                 //         } else {
            //                 //             [$name, $iri, $organism, $rank] = Self::getGNFMatches($name, $record);
            //                 //         }
            //                 //     }
            //                 // }
            //             }

            //             return view(
            //                 'forms.components.organism-info',
            //                 [
            //                     'data' => $data,
            //                 ],
            //             );
            //         })
            //         ->action(function (array $data, Organism $record): void {
            //             // Self::updateOrganismModel($data['name'], $data['iri'], $record, $data['rank']);
            //         })
            //         ->slideOver()
            // )
            ,
            Forms\Components\TextInput::make('iri')
                ->label('IRI')
                ->maxLength(255),
            Forms\Components\TextInput::make('rank')
                ->maxLength(255),
        ];
    }
}
