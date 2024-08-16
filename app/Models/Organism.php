<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    public function molecules()
    {
        return $this->belongsToMany(Molecule::class)->withPivot('id', 'organism_id', 'molecule_id', 'organism_parts')->withTimestamps();
    }

    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    public function sample_locations(): HasMany
    {
        return $this->hasMany(SampleLocation::class);
    }

    public function getIriAttribute($value)
    {
        return urldecode($value);
    }
}
