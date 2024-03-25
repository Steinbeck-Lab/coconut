<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Citation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doi',
        'title',
        'authors',
        'citation_text',
    ];

    /**
     * Get all of the collections that are assigned this citation.
     */
    public function collections(): MorphToMany
    {
        return $this->morphedByMany(Collection::class, 'citable');
    }

    /**
     * Get all of the molecules that are assigned this citation.
     */
    public function molecules(): MorphToMany
    {
        return $this->morphedByMany(Molecule::class, 'citable');
    }
}
