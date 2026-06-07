<?php

namespace App\Models;

use App\Filament\Traits\MutatesCollectionFormData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

/**
 * @property string|null $doi
 * @property string|null $doi_base
 * @property string|null $doi_suffix
 * @property array|null $datacite_schema
 * @property int $version
 * @property bool $is_latest
 * @property string|null $version_migration_status
 */
class Collection extends Model implements Auditable, HasMedia
{
    use HasDOI;
    use HasFactory;
    use HasTags;
    use InteractsWithMedia;
    use MutatesCollectionFormData;
    use \OwenIt\Auditing\Auditable;

    public const VERSION_MIGRATION_PENDING = 'pending';

    public const VERSION_MIGRATION_PROCESSING = 'processing';

    public const VERSION_MIGRATION_COMPLETE = 'complete';

    protected static function booted()
    {
        static::creating(fn ($collection) => $collection->uuid = Str::uuid());
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'jobs_status',
        'comments',
        'identifier',
        'url',
        'image',
        'status',
        'release_date',
        'datacite_schema',
        'parent_collection_id',
        'version',
        'is_latest',
        'superseded_by_collection_id',
        'superseded_at',
        'version_migration_status',
        'archived_entries_count',
        'archived_molecules_count',
        'doi_base',
        'doi_suffix',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'superseded_at' => 'datetime',
        'release_date' => 'datetime',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function citations(): MorphToMany
    {
        return $this->morphToMany(Citation::class, 'citable');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function molecules(): BelongsToMany
    {
        return $this->belongsToMany(Molecule::class)->withPivot('url', 'reference', 'mol_filename', 'structural_comments')->withTimestamps();
    }

    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    public function parentCollection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'parent_collection_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'superseded_by_collection_id');
    }

    public function versionRevocations(): HasMany
    {
        return $this->hasMany(CollectionVersionRevocation::class, 'lineage_root_id');
    }

    public function lineageRoot(): self
    {
        return $this->parent_collection_id
            ? self::query()->findOrFail($this->parent_collection_id)
            : $this;
    }

    public function lineageRootId(): int
    {
        return $this->parent_collection_id ?? $this->id;
    }

    /**
     * @return Builder<Collection>
     */
    public function lineageVersionsQuery(): Builder
    {
        $rootId = $this->lineageRootId();

        return self::query()
            ->where(function (Builder $q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_collection_id', $rootId);
            })
            ->orderByDesc('version');
    }

    public function isVersionMigrationActive(): bool
    {
        return in_array($this->version_migration_status, [
            self::VERSION_MIGRATION_PENDING,
            self::VERSION_MIGRATION_PROCESSING,
        ], true);
    }

    public function scopeEligibleForLegacyPipeline(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('version_migration_status')
                ->orWhere('version_migration_status', self::VERSION_MIGRATION_COMPLETE);
        });
    }

    public function isSuperseded(): bool
    {
        return $this->status === 'SUPERSEDED' || ! $this->is_latest;
    }

    public static function lineageDoiKey(string $identifier): string
    {
        return 'coconut.'.strtolower($identifier);
    }

    public function versionDoiSuffix(): string
    {
        return self::lineageDoiKey((string) $this->identifier).'.v'.$this->version;
    }

    public function baseDoiSuffix(): string
    {
        return self::lineageDoiKey((string) $this->identifier);
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }
}
