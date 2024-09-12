<?php

namespace App\Models;

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
}
