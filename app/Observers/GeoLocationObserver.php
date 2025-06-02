<?php

namespace App\Observers;

use App\Models\GeoLocation;
use Illuminate\Support\Facades\Cache;

class GeoLocationObserver
{
    /**
     * Handle the GeoLocation "created" event.
     */
    public function created(GeoLocation $geoLocation): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the GeoLocation "updated" event.
     */
    public function updated(GeoLocation $geoLocation): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Handle the GeoLocation "deleted" event.
     */
    public function deleted(GeoLocation $geoLocation): void
    {
        $this->clearStatsCaches();
    }

    /**
     * Clear all geo location-related statistics caches
     */
    private function clearStatsCaches(): void
    {
        Cache::forget('stats.geo_locations');
        Cache::forget('stats.geo_locations.distinct');
        Cache::forget('stats.molecules.with_geolocations');
    }
}
