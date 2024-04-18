<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Dispatcher;
use App\Events\ReportStatusChanged;
use App\Models\User;
use App\Notifications\ReportStatusChangedNotification;


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

    public function subscribe(Dispatcher $events): array
    {
        return [
            ReportStatusChanged::class => 'handleReportStatusChanged',
        ];
    }
}
