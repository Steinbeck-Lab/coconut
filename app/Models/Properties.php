<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Properties extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'molecule_id',
        'total_atom_count',
        'heavy_atom_count',
        'molecular_weight',
        'exact_molecular_weight',
        'molecular_formula',
        'alogp',
        'topological_polar_surface_area',
        'rotatable_bond_count',
        'hydrogen_bond_acceptors',
        'hydrogen_bond_donors',
        'hydrogen_bond_acceptors_lipinski',
        'hydrogen_bond_donors_lipinski',
        'lipinski_rule_of_five_violations',
        'aromatic_rings_count',
        'qed_drug_likeliness',
        'formal_charge',
        'fractioncsp3',
        'number_of_minimal_rings',
        'van_der_walls_volume',
        'contains_sugar',
        'contains_ring_sugars',
        'contains_linear_sugars',
        'fragments',
        'fragments_with_sugar',
        'murcko_framework',
        'np_likeness',
        'chemical_class',
        'chemical_sub_class',
        'chemical_super_class',
        'direct_parent_classification',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'chemical_class' => 'array',
        'chemical_sub_class' => 'array',
        'chemical_super_class' => 'array',
        'direct_parent_classification' => 'array',
    ];

    /**
     * Get the molecule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function molecule()
    {
        return $this->belongsTo(Molecule::class, 'molecule_id');
    }
}
