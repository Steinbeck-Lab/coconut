<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class License extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'spdx_id',
        'url',
        'description',
        'body',
        'category',
    ];

    /**
     * Define hasMany relation with collections
     *
     * @return HasMany
     */
    public function collections()
    {
        return $this->hasMany(Collection::class);
    }
}
