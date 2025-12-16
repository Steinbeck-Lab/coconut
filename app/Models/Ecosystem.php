<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Get the geo locations associated with this ecosystem.
     */
    public function geoLocations(): BelongsTo
    {
        return $this->belongsTo(GeoLocation::class, 'geo_location_id');
    }

    /**
     * Transform the audit data.
     */
    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
