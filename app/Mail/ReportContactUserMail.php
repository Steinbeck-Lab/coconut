<?php

namespace App\Mail;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportContactUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $report;

    public $user;

    public $contactMessage;

    public $curator;

    public $url;

    public function __construct(Report $report, $user, string $contactMessage, $curator, string $url)
    {
        $this->report = $report;
        $this->user = $user;
        $this->contactMessage = $contactMessage;
        $this->curator = $curator;
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

        $readableCategory = $categoryMap[$this->report->report_category] ?? ucfirst(strtolower($this->report->report_category));

        // Get the compound ID from molecules if available
        $compoundId = null;
        if ($this->report->molecules && $this->report->molecules->count() > 0) {
            $compoundId = $this->report->molecules->first()->identifier;
        }

        // Format: "CNP0197482.3 - Revocation: Title (Message from Curator)"
        if ($compoundId) {
            $subject = $compoundId.' - '.$readableCategory.': '.$this->report->title.' (Message from Curator)';
        } else {
            $subject = $readableCategory.': '.$this->report->title.' (Message from Curator)';
        }

        return $this->subject($subject)
            ->from($this->curator->email, $this->curator->name)
            ->replyTo($this->curator->email, $this->curator->name)
            ->markdown('mail.report.contactuser')
            ->with(['readableCategory' => $readableCategory]);
    }
}
