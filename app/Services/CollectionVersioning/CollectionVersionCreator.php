<?php

namespace App\Services\CollectionVersioning;

use App\Models\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class CollectionVersionCreator
{
    public function createFrom(Collection $source): Collection
    {
        if (! $source->is_latest || $source->status !== 'PUBLISHED') {
            throw new RuntimeException('Only the latest published collection can spawn a new version.');
        }

        if ($source->jobs_status === 'PROCESSING' || $source->jobs_status === 'QUEUED') {
            throw new RuntimeException('Collection is still processing. Wait until jobs complete.');
        }

        $root = $source->lineageRoot();
        $rootId = $root->id;

        $activeSibling = Collection::query()
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_collection_id', $rootId);
            })
            ->whereIn('version_migration_status', [
                Collection::VERSION_MIGRATION_PENDING,
                Collection::VERSION_MIGRATION_PROCESSING,
            ])
            ->exists();

        if ($activeSibling) {
            throw new RuntimeException('A version import is already in progress for this collection lineage.');
        }

        $nextVersion = (int) Collection::query()
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('parent_collection_id', $rootId);
            })
            ->max('version') + 1;

        $new = $source->replicate([
            'uuid',
            'doi',
            'doi_base',
            'doi_suffix',
            'datacite_schema',
            'jobs_status',
            'job_info',
            'successful_entries',
            'failed_entries',
            'molecules_count',
            'citations_count',
            'organisms_count',
            'geo_count',
            'total_entries',
            'release_date',
            'superseded_by_collection_id',
            'superseded_at',
            'archived_entries_count',
            'archived_molecules_count',
        ]);

        $new->parent_collection_id = $rootId;
        $new->version = $nextVersion;
        $new->is_latest = false;
        $new->status = 'DRAFT';
        $new->identifier = $source->identifier;
        $new->slug = Str::slug($root->slug.'-v'.$nextVersion);
        $new->version_migration_status = Collection::VERSION_MIGRATION_PENDING;
        $new->jobs_status = 'INCURATION';
        $new->job_info = '';
        $new->save();

        $tags = $source->tags()->get();
        if ($tags->isNotEmpty()) {
            $new->syncTags($tags);
        }

        return $new->fresh();
    }
}
