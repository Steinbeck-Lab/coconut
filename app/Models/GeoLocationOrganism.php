<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

class GeoLocationOrganism extends Pivot implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'geo_location_organism';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'geo_location_id',
        'organism_id',
    ];

    /**
     * Get the geo location that owns the relationship.
     */
    public function geoLocation(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class);
    }

    /**
     * Get the organism that owns the relationship.
     */
    public function organism(): BelongsTo
    {
        return $this->belongsTo(Organism::class);
    }

    /**
     * Transform the audit data.
     */
    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
