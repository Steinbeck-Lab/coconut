<?php

namespace App\Models;

use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lomkit\Rest\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class GeoLocation extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the organisms associated with this geo location.
     */
    public function organisms(): BelongsToMany
    {
        return $this->belongsToMany(Organism::class)
            ->using(GeoLocationOrganism::class)
            ->withTimestamps();
    }

    /**
     * Get the molecules associated with this geo location.
     */
    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class, 'geo_location_molecule', 'geo_location_id', 'molecule_id')
            ->withTimestamps();
    }

    /**
     * Get the ecosystems in this geo location.
     */
    public function ecosystems(): HasMany
    {
        return $this->hasMany(Ecosystem::class)->withTimestamps();
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    public static function getForm(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
        ];
    }
}
