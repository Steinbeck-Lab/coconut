<?php

namespace App\Models;

use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;

class Entry extends Model implements Auditable
{
    use HasFactory;
    use HasUUID;
    use \OwenIt\Auditing\Auditable;

    protected $casts = [
        'meta_data' => 'array',
        'synonyms' => 'array',
        'cm_data' => 'array',
        'has_rdkit_smiles_change' => 'boolean',
        'rdkit_restandardized_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'canonical_smiles',
        'reference_id',
        'doi',
        'link',
        'organism',
        'organism_part',
        'coconut_id',
        'mol_filename',
        'synonyms',
        'status',
        'errors',
        'standardized_canonical_smiles',
        'parent_canonical_smiles',
        'molecular_formula',
        'has_stereocenters',
        'is_invalid',
        'cm_data',
        'has_rdkit_smiles_change',
        'rdkit_restandardized_at',
        'submission_type',
        'meta_data',
        'is_archived',
    ];

    /**
     * Get the Collection this entry is associated with
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /**
     * Get the molecule this entry is associated with
     */
    public function molecule(): BelongsTo
    {
        return $this->belongsTo(Molecule::class, 'molecule_id');
    }

    /**
     * Get all of the reports for this entry.
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
