<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Citation extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doi',
        'title',
        'authors',
        'citation_text',
    ];

    /**
     * Get all of the collections that are assigned this citation.
     */
    public function collections(): MorphToMany
    {
        return $this->morphedByMany(Collection::class, 'citable');
    }

    /**
     * Get all of the molecules that are assigned this citation.
     */
    public function molecules(): MorphToMany
    {
        return $this->morphedByMany(Molecule::class, 'citable');
    }

    /**
     * Get all of the citations for the report.
     */
    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
