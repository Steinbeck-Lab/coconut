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
        $Curators = User::whereHas('roles')->where('id', '!=', 11)->get();
        $curatorIds = $Curators->pluck('id')->toArray();

        if ($event->report->status == ReportStatus::APPROVED->value || $event->report->status == ReportStatus::REJECTED->value) {
            // filter out notifications and mails to COCONUT Curator with id 11
            if ($event->report->user_id != 11 && ! in_array($event->report->user_id, $curatorIds)) {
                $ReportOwner = User::find($event->report->user_id);
                $ReportOwner->notify(new ReportStatusChangedNotification($event, 'owner'));
            }
        }
        foreach ($Curators as $Curator) {
            $Curator->notify(new ReportStatusChangedNotification($event, 'curator'));
        }
    }

    public function handleReportSubmitted(ReportSubmitted $event): void
    {
        $Curators = User::whereHas('roles')->where('id', '!=', 11)->get();
        $curatorIds = $Curators->pluck('id')->toArray();

        if ($event->report->user_id != 11 && ! in_array($event->report->user_id, $curatorIds)) {
            $ReportOwner = User::find($event->report->user_id);
            $ReportOwner->notify(new ReportSubmittedNotification($event, 'owner'));
        }
        foreach ($Curators as $Curator) {
            $Curator->notify(new ReportSubmittedNotification($event, 'curator'));
        }
    }

    public function handleReportAssigned(ReportAssigned $event): void
    {
        if ($event->report->user_id != 11) {
            $curator = User::find($event->curator_id);
            // Only proceed if curator exists
            if ($curator) {
                $curator->notify(new ReportAssignedNotification($event));
            }
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
