<?php

namespace App\Console\Commands;

use App\Mail\ExportNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExportNotification extends Command
{
    protected $signature = 'export:notify {subject} {status} {server} {timestamp} {error_message?} {error_details?}';

    protected $description = 'Send an export notification email';

    public function handle()
    {
        $this->info('Sending export notification email...');
        $subject = $this->argument('subject');
        $status = $this->argument('status');
        $server = $this->argument('server');
        $timestamp = $this->argument('timestamp');
        $error_message = $this->argument('error_message');
        $error_details = $this->argument('error_details');

        $recipients = config('mail.export_notification_recipients', [config('mail.from.address')]);

        foreach ($recipients as $recipient) {
            $this->info("Sending email to: $recipient");
            try {
                Mail::to($recipient)->queue(new ExportNotification(
                    $subject,
                    $status,
                    $server,
                    $timestamp,
                    $error_message,
                    $error_details
                ));
            } catch (\Exception $e) {
                $this->error("Failed to send email to: $recipient. Error: ".$e->getMessage());

            }

        }

        $this->info('Export notification email sent successfully!');

        return 0;
    }
}
