<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class MoleculeOrganism extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organism_id',
        'molecule_id',
        'sample_location_id',
        'collection_ids',
        'citation_ids',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'collection_ids' => 'array',
        'citation_ids' => 'array',
    ];

    /**
     * The attributes to exclude from the audit.
     *
     * @var array
     */
    protected $auditExclude = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the organism that this pivot record belongs to.
     */
    public function organism(): BelongsTo
    {
        return $this->belongsTo(Organism::class);
    }

    /**
     * Get the molecule that this pivot record belongs to.
     */
    public function molecule(): BelongsTo
    {
        return $this->belongsTo(Molecule::class);
    }

    /**
     * Get the sample location that this record belongs to.
     */
    public function sampleLocation(): BelongsTo
    {
        return $this->belongsTo(SampleLocation::class);
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
