<?php

namespace App\Observers;

use App\Models\Collection;
use Illuminate\Support\Facades\Cache;

class CollectionObserver
{
    /**
     * Handle the Collection "created" event.
     */
    public function created(Collection $collection): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Collection "updated" event.
     */
    public function updated(Collection $collection): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Collection "deleted" event.
     */
    public function deleted(Collection $collection): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Clear all collection-related statistics caches
     */
    private function clearStatsCaches(): void
    {
        Cache::forget('stats.collections');
    }
}
