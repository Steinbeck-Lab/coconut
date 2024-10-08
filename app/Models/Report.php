<?php

namespace App\Models;

use App\States\Report\ReportState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\ModelStates\HasStates;
use Spatie\Tags\HasTags;

class Report extends Model implements Auditable
{
    use HasFactory;
    use HasStates;
    use HasTags;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_type',
        'title',
        'evidence',
        'url',
        'mol_id_csv',
        'status',
        'comment',
        'user_id',
        'suggested_changes',
        'is_change',
    ];

    protected $casts = [
        'suggested_changes' => 'array',
        // 'status' => ReportState::class,
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

    public function organisms(): MorphToMany
    {
        return $this->morphedByMany(Organism::class, 'reportable');
    }

    /**
     * Get all of the users that are assigned this report.
     */
    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
