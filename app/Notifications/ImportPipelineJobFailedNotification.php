<?php

namespace App\Notifications;

use App\Events\ImportPipelineJobFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportPipelineJobFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $event;

    public function __construct(ImportPipelineJobFailed $event)
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
        $exceptionMessage = $this->event->exception->getMessage();
        $exceptionClass = get_class($this->event->exception);

        // Create a dashboard URL for admins to check logs
        $dashboardUrl = url(env('APP_URL').'/dashboard');

        $mailMessage = (new MailMessage)
            ->subject('Coconut: Import Pipeline Job Failed - '.$jobName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An import pipeline job has failed in the Coconut system.')
            ->line('**Job:** '.$jobName)
            ->line('**Error Type:** '.$exceptionClass)
            ->line('**Error Message:** '.$exceptionMessage);

        // Add batch information if available
        if ($this->event->batchId) {
            $mailMessage->line('**Batch ID:** '.$this->event->batchId);
        }

        // Add relevant job data if available
        if (! empty($this->event->jobData)) {
            $mailMessage->line('**Job Data:**');
            foreach ($this->event->jobData as $key => $value) {
                if (is_scalar($value)) {
                    $mailMessage->line('- '.ucfirst($key).': '.$value);
                }
            }
        }

        $mailMessage
            ->line('Please check the application logs for more details.')
            ->action('View Dashboard', $dashboardUrl)
            ->line('This is an automated notification from the Coconut import pipeline system.');

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
            'exception_message' => $this->event->exception->getMessage(),
            'exception_class' => get_class($this->event->exception),
            'batch_id' => $this->event->batchId,
            'job_data' => $this->event->jobData,
        ];
    }
}
