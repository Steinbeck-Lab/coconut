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
        // Convert category to readable format (REVOKE -> Revocation, SUBMISSION -> Submission, UPDATE -> Update)
        $categoryMap = [
            'SUBMISSION' => 'Submission',
            'REVOKE' => 'Revocation',
            'UPDATE' => 'Update',
        ];

        $readableCategory = $categoryMap[$this->event->report->report_category] ?? ucfirst(strtolower($this->event->report->report_category));

        if ($this->mail_to == 'owner') {
            // For submitter/owner - simpler format
            $subject = 'COCONUT: '.$readableCategory.' Request Received';
        } else {
            // For curators - format: "CNP0197482.3 - Revocation: Title"
            $compoundId = null;

            // Get the compound ID from molecules if available
            if ($this->event->report->molecules && $this->event->report->molecules->count() > 0) {
                $compoundId = $this->event->report->molecules->first()->identifier;
            }

            if ($compoundId) {
                $subject = $compoundId.' - '.$readableCategory.': '.$this->event->report->title;
            } else {
                $subject = $readableCategory.': '.$this->event->report->title;
            }
        }

        return $this->subject($subject)
            ->markdown('mail.report.submitted');
    }
}
