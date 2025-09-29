<?php

namespace App\Listeners;

use App\Events\ReportAssigned;
use App\Events\ReportStatusChanged;
use App\Events\ReportSubmitted;
use App\Models\User;
use App\Notifications\ReportAssignedNotification;
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
        if ($event->report->status == 'approved' || $event->report->status == 'rejected') {
            $ReportOwner = User::find($event->report->user_id);
            $ReportOwner->notify(new ReportStatusChangedNotification($event, 'owner'));
        }
        $Curators = User::whereHas('roles')->get();
        foreach ($Curators as $Curator) {
            $Curator->notify(new ReportStatusChangedNotification($event, 'curator'));
        }
    }

    public function handleReportSubmitted(ReportSubmitted $event): void
    {
        $ReportOwner = User::find($event->report->user_id);
        $ReportOwner->notify(new ReportSubmittedNotification($event, 'owner'));

        $Curators = User::whereHas('roles')->get();
        foreach ($Curators as $Curator) {
            $Curator->notify(new ReportSubmittedNotification($event, 'curator'));
        }
    }

    public function handleReportAssigned(ReportAssigned $event): void
    {
        $curator = User::find($event->curator_id);
        // Only proceed if curator exists
        if ($curator) {
            $curator->notify(new ReportAssignedNotification($event));
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ReportStatusChanged::class => 'handleReportStatusChanged',
            ReportSubmitted::class => 'handleReportSubmitted',
            ReportAssigned::class => 'handleReportAssigned',
        ];
    }
}
