<?php

namespace App\Listeners;

use App\Events\ImportPipelineJobFailed;
use App\Models\User;
use App\Notifications\ImportPipelineJobFailedNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
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

        $jobName = $event->jobName;
        $exceptionMessage = $event->errorDetails['message'];
        $exceptionClass = $event->errorDetails['class'];
        $timestamp = $event->errorDetails['timestamp'];

        // Get all users with admin roles (super_admin, admin, curator)
        $adminUsers = User::whereHas('roles')->get();

        foreach ($adminUsers as $user) {
            // $user->notify(new ImportPipelineJobFailedNotification($event));

            Notification::make()
                ->title('Import Pipeline Job Failed')
                ->body("Job: {$jobName} failed with error: {$exceptionMessage}")
                ->danger() // This makes it a red/error notification
                ->persistent() // This makes it stay until manually dismissed
                ->actions([
                    Action::make('mark_as_read')
                        ->label('Mark as Read')
                        ->close(),
                ])
                ->sendToDatabase($user);
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ImportPipelineJobFailed::class => 'handleImportPipelineJobFailed',
        ];
    }
}
