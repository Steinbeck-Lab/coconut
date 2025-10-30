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
        // Convert category to readable format
        $categoryMap = [
            'SUBMISSION' => 'Submission',
            'REVOKE' => 'Revocation',
            'UPDATE' => 'Update',
        ];

        $readableCategory = $categoryMap[$this->event->report->report_category] ?? ucfirst(strtolower($this->event->report->report_category));
        $readableStatus = ucwords(strtolower(str_replace('_', ' ', $this->event->report->status)));

        // Get the compound ID from molecules if available
        $compoundId = null;
        if ($this->event->report->molecules && $this->event->report->molecules->count() > 0) {
            $compoundId = $this->event->report->molecules->first()->identifier;
        }

        // Format: "CNP0197482.3 - Revocation: Title (Status update: Pending Approval)"
        if ($compoundId) {
            $subject = $compoundId.' - '.$readableCategory.': '.$this->event->report->title.' (Status update: '.$readableStatus.')';
        } else {
            $subject = $readableCategory.': '.$this->event->report->title.' (Status update: '.$readableStatus.')';
        }

        return $this->subject($subject)
            ->markdown('mail.report.statuschanged')
            ->with(['readableCategory' => $readableCategory]);
    }
}
