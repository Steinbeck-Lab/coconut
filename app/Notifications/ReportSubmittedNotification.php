<?php

namespace App\Notifications;

use App\Events\ReportSubmitted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(env('APP_URL').'/dashboard/reports/'.$this->event->report->id);

        $subject_prefix = '';
        if ($this->mail_to == 'owner') {
            $subject_prefix = 'Coconut: Your report "';
        } else {
            $subject_prefix = 'Coconut: A new report "';
        }

        return (new MailMessage)
            ->subject($subject_prefix.$this->event->report->title.'" has been submitted.')
            ->markdown('mail.report.submitted', ['url' => $url, 'event' => $this->event, 'user' => $notifiable, 'mail_to' => $this->mail_to]);
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
