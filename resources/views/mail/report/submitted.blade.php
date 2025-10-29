@component('mail::message')
# {{ $event->report->report_category }} Request Submitted

Hello {{ $user->name }}!

@if ($mail_to == 'owner')
@if ($event->report->report_category == 'REVOKE')
Thank you for your revocation request and for contributing to the curation process. It is pending review with our Curators. You will receive further updates via email.
@elseif ($event->report->report_category == 'UPDATE')
Thank you for your update request and for contributing to the curation process. It is pending review with our Curators. You will receive further updates via email.
@else
Thank you for your submission and for contributing to the curation process. It is pending review with our Curators. You will receive further updates via email.
@endif
@else
A {{ $event->report->report_category }} request has been submitted. Please review and take necessary actions.
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
{{ config('app.name') }}
@endcomponent
