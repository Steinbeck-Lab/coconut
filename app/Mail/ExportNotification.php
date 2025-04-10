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

    public function __construct($subject, $status, $server, $timestamp, $error_message = null, $error_details = null)
    {
        $this->subject = $subject;
        $this->status = $status;
        $this->server = $server;
        $this->timestamp = $timestamp;
        $this->error_message = $error_message;
        $this->error_details = $error_details;
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->view('emails.export-notification');
    }
}
