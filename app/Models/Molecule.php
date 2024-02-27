<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Molecule extends Model
{
    use HasFactory;

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
}
