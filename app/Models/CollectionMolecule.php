<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class CollectionMolecule extends Model implements Auditable
{
    use AuditableTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'collection_molecule';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'collection_id',
        'molecule_id',
        'reference',
        'url',
        'mol_filename',
        'structural_comments',
        // Add other fields as needed
    ];

    /**
     * Should the timestamps be audited?
     *
     * @var bool
     */
    protected $auditTimestamps = false;

    /**
     * Get the collection that this pivot belongs to
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /**
     * Get the molecule that this pivot belongs to
     */
    public function molecule(): BelongsTo
    {
        return $this->belongsTo(Molecule::class, 'molecule_id');
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
