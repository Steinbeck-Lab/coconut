<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public $curator;

    public $mail_to;

    public $url;

    public function __construct($event, $curator, string $mail_to, string $url)
    {
        $this->event = $event;
        $this->curator = $curator;
        $this->mail_to = $mail_to;
        $this->url = $url;
    }

    public function build()
    {
        $subject = 'Coconut: '.$this->event->report->report_category.' request assigned to you';

        return $this->subject($subject)
            ->view('mail.report.assigned');
    }
}
