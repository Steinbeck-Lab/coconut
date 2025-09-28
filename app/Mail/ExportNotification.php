<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $status;

    public $server;

    public $timestamp;

    public $error_message;

    public $error_details;

    public $backup_stats;

    public $is_daily_backup;

    public function __construct(
        $subject,
        $status,
        $server,
        $timestamp,
        $error_message = null,
        $error_details = null,
        $backup_stats = null
    ) {
        $this->subject = $subject;
        $this->status = $status;
        $this->server = $server;
        $this->timestamp = $timestamp;
        $this->error_message = $error_message;
        $this->error_details = $error_details;
        $this->backup_stats = $backup_stats;

        // Determine if this is a daily backup based on backup_stats
        $this->is_daily_backup = $backup_stats &&
            isset($backup_stats['backup_type']) &&
            $backup_stats['backup_type'] === 'Private Daily Backup';
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->view('emails.export-notification')
            ->with([
                'operation_type' => $this->is_daily_backup ? 'Daily Backup' : 'Monthly Export',
            ]);
    }
}
