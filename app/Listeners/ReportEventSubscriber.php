<?php

namespace App\Listeners;

use App\Enums\ReportStatus;
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
        // Send email to report owner for APPROVED, REJECTED, and PENDING_APPROVAL statuses
        if ($event->report->status == ReportStatus::APPROVED->value ||
            $event->report->status == ReportStatus::REJECTED->value ||
            $event->report->status == ReportStatus::PENDING_APPROVAL->value ||
            $event->report->status == ReportStatus::PENDING_REJECTION->value) {
            $ReportOwner = User::find($event->report->user_id);
            $ReportOwner->notify(new ReportStatusChangedNotification($event, 'owner'));
        }

        // Always notify curators about status changes
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
