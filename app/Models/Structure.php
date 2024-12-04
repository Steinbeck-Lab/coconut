<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Structure extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'molecule_id',
        '2d',
        '3d',
        'mol',
    ];

    public function molecule()
    {
        return $this->belongsTo(Molecule::class, 'molecule_id');
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
