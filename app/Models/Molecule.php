<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Tags\HasTags;

class Molecule extends Model implements Auditable
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
        'inchi',
        'standard_inchi',
        'inchi_key',
        'standard_inchi_key',
        'canonical_smiles',
        'sugar_free_smiles',
        'identifier',
        'name',
        'cas',
        'synonyms',
        'iupac_name',
        'murko_framework',
        'structural_comments',

        'name_trust_level',
        'annotation_level',
        'parent_id',
        'variants_count',

        'active',
        'has_variants',
        'has_stereo',
        'is_parent',
        'is_placeholder'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'synonyms' => 'array',
    ];

    /**
     * Get the properties associated with the molecule.
     */
    public function properties(): HasOne
    {
        return $this->hasOne(Properties::class);
    }
}
