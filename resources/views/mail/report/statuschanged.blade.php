@component('mail::message')
# {{ $event->report->report_category }} Request Status Changed

Hello {{ $user->name }}!

@if ($mail_to == 'owner')
@if ($event->report->status === \App\Enums\ReportStatus::PENDING_APPROVAL->value)
The status of your request has been changed. Our curator accepted your request and is waiting for a second curator approval before being accepted.

No action is required at the moment from your end.
@elseif ($event->report->status === \App\Enums\ReportStatus::APPROVED->value)
Your request is now approved. Feel free to reach out if you have any questions.

Please note it might take some time before our indexes and exports are updated.
@elseif ($event->report->status === \App\Enums\ReportStatus::REJECTED->value)
Your {{ strtolower($event->report->report_category) }} request has been rejected by one of our curators and is waiting for another review. No further action is required.
@else
The status of your {{ $event->report->report_category }} request has been changed. Please review the updated status and feel free to reach out if you have any questions.
@endif
@else
@if ($event->report->status === \App\Enums\ReportStatus::APPROVED->value || $event->report->status === \App\Enums\ReportStatus::REJECTED->value)
The status of the {{ $event->report->report_category }} request has been changed. No further action is required.
@else
The status of the {{ $event->report->report_category }} request has been changed. Please review the updated status and take necessary actions.
@endif
@endif

## Compound Details
@if ($event->report->molecules && $event->report->molecules->count() > 0)
@foreach ($event->report->molecules as $molecule)
**COCONUT ID:** [{{ $molecule->identifier }}]({{ config('app.url') }}/compound/{{ $molecule->identifier }})  
**Compound Name:** {{ $molecule->name ?? 'N/A' }}
@endforeach
@else
_No compound information available_
@endif

## Report Information
**Title:** {{ $event->report->title }}  
@if ($event->report->evidence)
**Evidence/Comment:** {{ $event->report->evidence }}  
@endif
@if ($event->report->doi)
**DOI:** [{{ $event->report->doi }}](https://doi.org/{{ $event->report->doi }})  
@endif
@if ($event->report->comment)
**Comment:** {{ $event->report->comment }}  
@endif
**Status:** {{ ucwords(strtolower(str_replace('_', ' ', $event->report->status))) }}

@component('mail::button', ['url' => $url])
View Report
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
