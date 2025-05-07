<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Ecosystem extends Model implements Auditable
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
        'description',
    ];

    /**
     * Get the organisms associated with this ecosystem.
     */
    public function organisms(): BelongsToMany
    {
        return $this->belongsToMany(Organism::class, 'molecule_organism', 'ecosystem_id', 'organism_id')
            ->withTimestamps();
    }

    /**
     * Get the geo locations associated with this ecosystem.
     */
    public function geoLocations(): BelongsToMany
    {
        return $this->belongsToMany(GeoLocation::class, 'molecule_organism', 'ecosystem_id', 'geo_location_id')
            ->withTimestamps();
    }

    /**
     * Get the molecules found in this ecosystem.
     */
    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class, 'molecule_organism', 'ecosystem_id', 'molecule_id')
            ->withTimestamps();
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
