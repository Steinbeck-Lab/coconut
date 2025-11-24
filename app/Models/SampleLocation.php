<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'collection_ids',
        'molecule_count',
    ];

    protected $casts = [
        'collection_ids' => 'array',
    ];

    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class, 'molecule_organism', 'sample_location_id', 'molecule_id')
            ->withTimestamps()
            ->distinct('molecule_id')
            ->orderBy('molecule_id');
    }

    public function organisms(): BelongsToMany
    {
        return $this->belongsToMany(Organism::class, 'molecule_organism', 'sample_location_id', 'organism_id')
            ->withTimestamps()
            ->distinct('organism_id')
            ->orderBy('organism_id');
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
