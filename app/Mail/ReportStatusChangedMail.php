<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public $user;

    public $mail_to;

    public $url;

    public function __construct($event, $user, string $mail_to, string $url)
    {
        $this->event = $event;
        $this->user = $user;
        $this->mail_to = $mail_to;
        $this->url = $url;
    }

    public function build()
    {
        $subject = 'Coconut: Report status changed to "'.$this->event->report->status.'"';

        return $this->subject($subject)
            ->view('mail.report.statuschanged');
    }
}
