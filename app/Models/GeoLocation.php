<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

class GeoLocation extends Model implements Auditable
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
    ];

    /**
     * Get the organisms associated with this geo location.
     */
    // public function organisms(): BelongsToMany
    // {
    //     return $this->belongsToMany(Organism::class, 'geo_location_organism')
    //         ->using(GeoLocationOrganism::class)
    //         ->withTimestamps();
    // }
    public function organisms(): BelongsToMany
    {
        return $this->belongsToMany(Organism::class, 'molecule_organism', 'geo_location_id', 'organism_id')
            ->withTimestamps()
            ->distinct('organism_id')
            ->orderBy('organism_id');
    }

    /**
     * Get the molecules associated with this geo location.
     */
    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class, 'molecule_organism', 'geo_location_id', 'molecule_id')
            ->withTimestamps()
            ->distinct('molecule_id')
            ->orderBy('molecule_id');
    }

    /**
     * Get the ecosystems in this geo location.
     */
    public function ecosystems(): BelongsToMany
    {
        return $this->belongsToMany(Ecosystem::class, 'molecule_organism', 'geo_location_id', 'ecosystem_id')
            ->withTimestamps()
            ->distinct('ecosystem_id')
            ->orderBy('ecosystem_id');
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
        ];
    }
}
