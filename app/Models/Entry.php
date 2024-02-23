<?php

namespace App\Models;

use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    use HasFactory;
    use HasUUID;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'canonical_smiles',
        'identifier',
        'doi',
        'link',
        'organism',
        'organism_part',
        'coconut_id',
        'mol_filename',
        'status',
        'errors',
        'standardized_canonical_smiles',
        'parent_canonical_smiles',
        'has_stereocenters',
        'is_invalid',
        'cm_data',
    ];

    /**
     * Get the license of the project.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }
}
