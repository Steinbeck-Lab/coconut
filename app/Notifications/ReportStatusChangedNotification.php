<?php

namespace App\Notifications;

use App\Events\ReportStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $event;

    public $mail_to;

    public function __construct(ReportStatusChanged $event, string $mail_to)
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
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(env('APP_URL').'/dashboard/reports/'.$this->event->report->id.'?activeRelationManager=3');

        $subject_prefix = '';
        if ($this->mail_to == 'owner') {
            $subject_prefix = 'Coconut: Status changed for your Report: ';
        } else {
            $subject_prefix = 'Coconut: Status changed for the Report: ';
        }

        return (new MailMessage)
            ->subject($subject_prefix.$this->event->report->title)
            ->markdown('mail.report.statuschanged', ['url' => $url, 'event' => $this->event, 'user' => $notifiable, 'mail_to' => $this->mail_to]);
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
