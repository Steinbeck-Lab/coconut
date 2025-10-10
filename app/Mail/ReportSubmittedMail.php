<?php

namespace App\Mail;

use App\Events\ReportSubmitted;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $event;

    public $user;

    public $mail_to;

    public $url;

    public function __construct(ReportSubmitted $event, $user, string $mail_to, string $url)
    {
        $this->event = $event;
        $this->user = $user;
        $this->mail_to = $mail_to;
        $this->url = $url;
    }

    public function build()
    {
        $subject_prefix = $this->mail_to == 'owner' ? 'Coconut: Your ' : 'Coconut: A new ';
        $subject_prefix .= $this->event->report->report_category.' request is submitted: "';

        return $this->subject($subject_prefix.$this->event->report->title.'"')
            ->view('mail.report.submitted');
    }
}
