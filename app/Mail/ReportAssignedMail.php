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
        // Convert category to readable format
        $categoryMap = [
            'SUBMISSION' => 'Submission',
            'REVOKE' => 'Revocation',
            'UPDATE' => 'Update',
        ];

        $readableCategory = $categoryMap[$this->event->report->report_category] ?? ucfirst(strtolower($this->event->report->report_category));

        $subject = 'COCONUT: '.$readableCategory.' Request Assigned to You - '.$this->event->report->title;

        return $this->subject($subject)
            ->markdown('mail.report.assigned')
            ->with(['readableCategory' => $readableCategory]);
    }
}
