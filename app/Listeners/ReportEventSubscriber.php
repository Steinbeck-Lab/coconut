<?php

namespace App\Listeners;

use App\Events\ReportStatusChanged;
use App\Events\ReportSubmitted;
use App\Models\User;
use App\Notifications\ReportStatusChangedNotification;
use App\Notifications\ReportSubmittedNotification;
use Illuminate\Events\Dispatcher;

class ReportEventSubscriber
{
    /**
     * Create the event listener.
     */
    public $ReportOwner;

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handleReportStatusChanged(ReportStatusChanged $event): void
    {
        $ReportOwner = User::find($event->report->user_id);
        $ReportOwner->notify(new ReportStatusChangedNotification($event));
    }

    public function handleReportSubmitted(ReportSubmitted $event): void
    {
        $ReportOwner = User::find($event->report->user_id);
        $ReportOwner->notify(new ReportSubmittedNotification($event));
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ReportStatusChanged::class => 'handleReportStatusChanged',
            ReportSubmitted::class => 'handleReportSubmitted',
        ];
    }
}
