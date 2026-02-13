<?php

namespace App\Models;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'description',
        'country',
        'country_code',
        'county',
        'flag',
        'latitude',
        'longitude',
        'boundary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'boundary' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
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
        return $this->hasMany(Ecosystem::class);
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    public static function getForm(): array
    {
        return [
            Section::make('Basic Information')
                ->description('Enter the primary details of the geographic location.')
                ->schema([
                    TextInput::make('name')
                        ->label('Location Name')
                        ->placeholder('e.g., Amazon Rainforest, Mediterranean Sea')
                        ->helperText('Enter the full name of the geographic location')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Add details about this location, climate, ecosystem type, etc.')
                        ->helperText('Optional notes about the location')
                        ->rows(3),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->collapsible(),

            Section::make('Geographic Details')
                ->description('Specify country, region, and administrative information.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('country_code')
                                ->label('Country')
                                ->options(function () {
                                    // countries() returns arrays keyed by ISO code
                                    $options = [];
                                    foreach (countries() as $code => $data) {
                                        $name = $data['name']['common'] ?? $data['name'] ?? $code;
                                        $emoji = $data['emoji'] ?? '🌍';
                                        $options[$code] = $emoji.' '.$name;
                                    }

                                    return $options;
                                })
                                ->searchable()
                                ->helperText('Select country - flag will be auto-filled')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // country($code) returns a Country object
                                        $countryData = country($state);
                                        if ($countryData) {
                                            $set('country', $countryData->getName());
                                            $set('flag', $countryData->getEmoji());
                                        }
                                    }
                                }),

                            TextInput::make('county')
                                ->label('County/Province/State')
                                ->placeholder('e.g., Amazonas, California')
                                ->helperText('Regional subdivision'),
                        ]),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->collapsible(),

            Section::make('Coordinates & Boundaries')
                ->description('Define the precise location and area coverage.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('latitude')
                                ->label('Latitude')
                                ->placeholder('e.g., -3.4653')
                                ->helperText('Decimal degrees (-90 to 90)')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90)
                                ->step(0.00000001),

                            TextInput::make('longitude')
                                ->label('Longitude')
                                ->placeholder('e.g., -62.2159')
                                ->helperText('Decimal degrees (-180 to 180)')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180)
                                ->step(0.00000001),
                        ]),

                    Textarea::make('boundary')
                        ->label('Boundary (JSON)')
                        ->placeholder('[[-5.0, -60.0], [-5.0, -70.0], [0.0, -70.0], [0.0, -60.0]]')
                        ->helperText('Array of [latitude, longitude] coordinates defining the boundary polygon')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull()
                ->collapsible(),
        ];
    }
}
