<?php

namespace App\Notifications;

use App\Events\ReportSubmitted;
use App\Mail\ReportSubmittedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $event;

    public $mail_to;

    public function __construct(ReportSubmitted $event, string $mail_to)
    {
        $this->event = $event;
        $this->mail_to = $mail_to;
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
        if ($notifiable->can('update', $this->event->report)) {
            $url = url(config('app.url').'/dashboard/reports/'.$this->event->report->id.'/edit');
        } else {
            $url = url(config('app.url').'/dashboard/reports/'.$this->event->report->id);
        }

        return (new ReportSubmittedMail($this->event, $notifiable, $this->mail_to, $url))
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
