<?php

namespace App\Notifications;

use App\Mail\ReportContactUserMail;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportContactUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $report;

    public $message;

    public $curator;

    public function __construct(Report $report, string $message, $curator)
    {
        $this->report = $report;
        $this->message = $message;
        $this->curator = $curator;
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
    public function toMail(object $notifiable)
    {
        $url = url(env('APP_URL').'/dashboard/reports/'.$this->report->id);

        return (new ReportContactUserMail($this->report, $notifiable, $this->message, $this->curator, $url))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
