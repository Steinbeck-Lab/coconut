<?php

namespace App\Observers;

use App\Models\Organism;
use Illuminate\Support\Facades\Cache;

class OrganismObserver
{
    /**
     * Handle the Organism "created" event.
     */
    public function created(Organism $organism): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Organism "updated" event.
     */
    public function updated(Organism $organism): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Organism "deleted" event.
     */
    public function deleted(Organism $organism): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Clear all organism-related statistics caches
     */
    private function clearStatsCaches(): void
    {
        Cache::forget('stats.organisms');
        Cache::forget('stats.organisms.with_iri');
        Cache::forget('stats.molecules.with_organisms');
    }
}
