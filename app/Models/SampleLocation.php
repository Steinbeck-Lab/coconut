<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;

class SampleLocation extends Model implements Auditable
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
        'slug',
        'organism_id',
        'collection_ids',
        'molecule_count',
    ];

    protected $casts = [
        'collection_ids' => 'array',
    ];

    public function organisms(): HasOne
    {
        return $this->hasOne(Organism::class);
    }

    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class)->withTimestamps();
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
