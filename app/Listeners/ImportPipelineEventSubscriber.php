<?php

namespace App\Listeners;

use App\Events\ImportPipelineJobFailed;
use App\Models\User;
use App\Notifications\ImportPipelineJobFailedNotification;
use Illuminate\Events\Dispatcher;

class ImportPipelineEventSubscriber
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the import pipeline job failed event.
     */
    public function handleImportPipelineJobFailed(ImportPipelineJobFailed $event): void
    {
        // Get all users with admin roles (super_admin, admin, curator)
        $adminUsers = User::whereHas('roles')->get();

        foreach ($adminUsers as $user) {
            $user->notify(new ImportPipelineJobFailedNotification($event));
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ImportPipelineJobFailed::class => 'handleImportPipelineJobFailed',
        ];
    }
}
