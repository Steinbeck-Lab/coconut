<?php

namespace App\Notifications;

use App\Events\PrePublishJobFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrePublishJobFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $event;

    public function __construct(PrePublishJobFailed $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $jobName = $this->event->jobName;
        $exceptionMessage = $this->event->errorDetails['message'];
        $exceptionClass = $this->event->errorDetails['class'];
        $timestamp = $this->event->errorDetails['timestamp'];

        // Create a dashboard URL for admins to check logs
        $dashboardUrl = url(env('APP_URL').'/dashboard');

        $mailMessage = (new MailMessage)
            ->subject('Coconut: Pre Publish Job Failed - '.$jobName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A pre publish job has failed in the Coconut system.')
            ->line('**Process:** '.$jobName)
            ->line('**Error Type:** '.$exceptionClass)
            ->line('**Error Message:** '.$exceptionMessage)
            ->line('**Timestamp:** '.$timestamp);

        // Add batch information if available
        if ($this->event->batchId) {
            $mailMessage->line('**Batch ID:** '.$this->event->batchId);
        }

        // Add batch statistics if available
        if (! empty($this->event->batchStats)) {
            $mailMessage->line('**Batch Statistics:**');
            $stats = $this->event->batchStats;

            if (isset($stats['collection_name'])) {
                $mailMessage->line('- Collection: '.$stats['collection_name']);
            }
            if (isset($stats['total_jobs'])) {
                $mailMessage->line('- Total Jobs: '.$stats['total_jobs']);
            }
            if (isset($stats['processed_jobs'])) {
                $mailMessage->line('- Processed Jobs: '.$stats['processed_jobs']);
            }
            if (isset($stats['failed_jobs'])) {
                $mailMessage->line('- Failed Jobs: '.$stats['failed_jobs']);
            }
            if (isset($stats['pending_jobs'])) {
                $mailMessage->line('- Pending Jobs: '.$stats['pending_jobs']);
            }
            if (isset($stats['progress'])) {
                $mailMessage->line('- Progress: '.number_format($stats['progress'], 2).'%');
            }
            if (isset($stats['finished_at']) && $stats['finished_at']) {
                $mailMessage->line('- Finished At: '.$stats['finished_at']);
            }
        }

        $mailMessage
            ->line('Please check the application logs for more details.')
            ->action('View Dashboard', $dashboardUrl)
            ->line('This is an automated notification from the Coconut pre publish system.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_name' => $this->event->jobName,
            'exception_message' => $this->event->errorDetails['message'],
            'exception_class' => $this->event->errorDetails['class'],
            'timestamp' => $this->event->errorDetails['timestamp'],
            'batch_id' => $this->event->batchId,
            'batch_stats' => $this->event->batchStats,
        ];
    }
}
