<?php

namespace App\Observers;

use App\Models\Molecule;
use Illuminate\Support\Facades\Cache;

class MoleculeObserver
{
    /**
     * Handle the Molecule "created" event.
     */
    public function created(Molecule $molecule): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the Molecule "updated" event.
     */
    public function updated(Molecule $molecule): void
    {
        Cache::forget("molecules.{$molecule->identifier}");
        $this->clearStatsCaches();
    }

    /**
     * Handle the Molecule "deleted" event.
     */
    public function deleted(Molecule $molecule): void
    {
        Cache::forget("molecules.{$molecule->identifier}");
        $this->clearStatsCaches();
    }

    /**
     * Clear all molecule-related statistics caches
     */
    private function clearStatsCaches(): void
    {
        Cache::forget('stats.molecules');
        Cache::forget('stats.molecules.non_stereo');
        Cache::forget('stats.molecules.stereo');
        Cache::forget('stats.molecules.parent');
        Cache::forget('stats.molecules.with_organisms');
        Cache::forget('stats.molecules.with_citations');
        Cache::forget('stats.molecules.with_geolocations');
        Cache::forget('stats.molecules.revoked');
    }
}
