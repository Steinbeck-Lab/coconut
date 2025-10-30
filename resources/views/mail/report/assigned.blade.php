@component('mail::message')
Hello {{ $curator->name }}!

As a COCONUT curator, you have been assigned a {{ $readableCategory }} request. Please review and leave your comments.

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
Review Report
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
