<?php

namespace App\Observers;

use App\Models\Citation;
use Illuminate\Support\Facades\Cache;

class CitationObserver
{
    /**
     * Handle the Citation "created" event.
     */
    public function created(Citation $citation): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Citation "updated" event.
     */
    public function updated(Citation $citation): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Citation "deleted" event.
     */
    public function deleted(Citation $citation): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Clear all citation-related statistics caches
     */
    private function clearStatsCaches(): void
    {
        Cache::forget('stats.citations');
        Cache::forget('stats.molecules.with_citations');
    }
}
