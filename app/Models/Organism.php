<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organism extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'iri',
        'rank',
    ];

    public function molecules()
    {
        return $this->belongsToMany(Molecule::class)->withPivot('id', 'organism_parts')->withTimestamps();
    }
}
