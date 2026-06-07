<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionVersionRevocation extends Model
{
    protected $fillable = [
        'lineage_root_id',
        'from_collection_id',
        'to_collection_id',
        'entry_id',
        'molecule_id',
        'reference_id',
        'standardized_canonical_smiles',
        'revoked_at',
        'reason',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function lineageRoot(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'lineage_root_id');
    }

    public function fromCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'from_collection_id');
    }

    public function toCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'to_collection_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function molecule(): BelongsTo
    {
        return $this->belongsTo(Molecule::class);
    }
}
