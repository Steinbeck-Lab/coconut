<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Tags\HasTags;

class Report extends Model implements Auditable
{
    use HasFactory;
    use HasTags;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'evidence',
        'url',
        'mol_id_csv',
        'status',
        'comment',
        'user_id',

    ];

    /**
     * Get all of the collections that are assigned this report.
     */
    public function collections(): MorphToMany
    {
        return $this->morphedByMany(Collection::class, 'reportable');
    }

    /**
     * Get all of the molecules that are assigned this repot.
     */
    public function molecules(): MorphToMany
    {
        return $this->morphedByMany(Molecule::class, 'reportable');
    }

    /**
     * Get all of the citations that are assigned this report.
     */
    public function citations(): MorphToMany
    {
        return $this->morphedByMany(Citation::class, 'reportable');
    }

    /**
     * Get all of the users that are assigned this report.
     */
    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
