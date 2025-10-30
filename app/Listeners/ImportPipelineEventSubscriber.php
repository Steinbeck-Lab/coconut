<?php

namespace App\Listeners;

use App\Events\PostPublishJobFailed;
use App\Events\PrePublishJobFailed;
use App\Models\User;
use App\Notifications\PostPublishJobFailedNotification;
use App\Notifications\PrePublishJobFailedNotification;
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
     * Handle the post publish job failed event.
     */
    public function handlePostPublishJobFailed(PostPublishJobFailed $event): void
    {

        $jobName = $event->jobName;
        $exceptionMessage = $event->errorDetails['message'];
        $exceptionClass = $event->errorDetails['class'];
        $timestamp = $event->errorDetails['timestamp'];

        // Get all users with admin roles (super_admin, admin, curator)
        $adminUsers = User::whereHas('roles')->where('id', '!=', 11)->get();

        foreach ($adminUsers as $user) {
            // $user->notify(new PostPublishJobFailedNotification($event));

            Notification::make()
                ->title('Post Publish Job Failed')
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

    /**
     * Handle the pre publish job failed event.
     */
    public function handlePrePublishJobFailed(PrePublishJobFailed $event): void
    {
        $jobName = $event->jobName;
        $exceptionMessage = $event->errorDetails['message'];
        $exceptionClass = $event->errorDetails['class'];
        $timestamp = $event->errorDetails['timestamp'];

        // Build notification body with batch statistics if available
        $notificationBody = "Job: {$jobName} failed with error: {$exceptionMessage}";

        if (! empty($event->batchStats)) {
            $stats = $event->batchStats;
            $notificationBody .= "\n\nBatch Statistics:";

            if (isset($stats['collection_name'])) {
                $notificationBody .= "\n Collection: ".$stats['collection_name'];
            }
            if (isset($stats['failed_jobs'], $stats['total_jobs'])) {
                $notificationBody .= "\n Failed Jobs: ".$stats['failed_jobs'].' / '.$stats['total_jobs'];
            }
            if (isset($stats['progress'])) {
                $notificationBody .= "\n Progress: ".number_format($stats['progress'], 2).'%';
            }
        }

        // Get all users with admin roles (super_admin, admin, curator)
        $adminUsers = User::whereHas('roles')->where('id', '!=', 11)->get();

        foreach ($adminUsers as $user) {
            $user->notify(new PrePublishJobFailedNotification($event));

            Notification::make()
                ->title('Pre Publish Job Failed')
                ->body($notificationBody)
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
            PostPublishJobFailed::class => 'handlePostPublishJobFailed',
            PrePublishJobFailed::class => 'handlePrePublishJobFailed',
        ];
    }
}
