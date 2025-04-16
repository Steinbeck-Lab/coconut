<?php

namespace App\Console\Commands;

use App\Mail\ExportNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendExportNotification extends Command
{
    protected $signature = 'export:notify 
                            {subject : Email subject} 
                            {status : Status (success/error)} 
                            {server : Server name} 
                            {timestamp : Timestamp} 
                            {error_message? : Error message if status is error} 
                            {error_details? : Detailed error information}
                            {--stats= : JSON string containing backup statistics}';

    protected $description = 'Send an export notification email with detailed information';

    public function handle()
    {
        $this->info('Sending export notification email...');

        $subject = $this->argument('subject');
        $status = $this->argument('status');
        $server = $this->argument('server');
        $timestamp = $this->argument('timestamp');
        $error_message = $this->argument('error_message');
        $error_details = $this->argument('error_details');

        // Parse backup stats if provided
        $backupStats = null;
        if ($this->option('stats')) {
            try {
                $backupStats = json_decode($this->option('stats'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->warn('Warning: Could not parse backup stats JSON: '.json_last_error_msg());
                }
            } catch (\Exception $e) {
                $this->warn('Warning: Error parsing backup stats: '.$e->getMessage());
            }
        }

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
                    $error_details,
                    $backupStats
                ));
            } catch (\Exception $e) {
                $this->error("Failed to send email to: $recipient. Error: ".$e->getMessage());
            }
        }

        $this->info('Export notification email queued successfully!');

        return 0;
    }
}
