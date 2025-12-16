<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class MoleculeOrganism extends Pivot implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'molecule_organism';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organism_id',
        'molecule_id',
        'sample_location_id',
        'geo_location_id',
        'ecosystem_id',
        'collection_ids',
        'citation_ids',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'collection_ids' => 'array',
        'citation_ids' => 'array',
        'metadata' => 'array',
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

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
