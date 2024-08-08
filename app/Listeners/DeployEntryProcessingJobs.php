<?php

namespace App\Listeners;

use App\Events\ImportedCSVProcessed;

class DeployEntryProcessingJobs
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportedCSVProcessed $event): void {}
}
