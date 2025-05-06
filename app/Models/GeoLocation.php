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
    public function organisms(): BelongsToMany
    {
        return $this->belongsToMany(Organism::class, 'geo_location_organism')
            ->using(GeoLocationOrganism::class)
            ->withTimestamps();
    }

    /**
     * Get the molecules associated with this geo location.
     */
    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class, 'molecule_organism', 'geo_location_id', 'molecule_id')
            ->withTimestamps();
    }

    /**
     * Get the ecosystems in this geo location.
     */
    public function ecosystems(): BelongsToMany
    {
        return $this->belongsToMany(Ecosystem::class, 'molecule_organism', 'geo_location_id', 'ecosystem_id')
            ->withTimestamps();
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
