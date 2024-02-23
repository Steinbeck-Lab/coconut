<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Citation extends Model
{
    use HasFactory;

    /**
     * Get all of the posts that are assigned this tag.
     */
    public function collections(): MorphToMany
    {
        return $this->morphedByMany(Collection::class, 'citable');
    }
}
