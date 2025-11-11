@component('mail::message')
Hello {{ $user->name }}!

**{{ $curator->name }}** has sent you a message regarding your {{ strtolower($readableCategory) }} request:

@component('mail::panel')
{{ $contactMessage }}
@endcomponent

## Compound Details
@if ($report->molecules && $report->molecules->count() > 0)
@foreach ($report->molecules as $molecule)
**COCONUT ID:** [{{ $molecule->identifier }}]({{ config('app.url') }}/compound/{{ $molecule->identifier }})  
**Compound Name:** {{ $molecule->name ?? 'N/A' }}
@endforeach
@else
_No compound information available_
@endif

## Report Information
**Title:** {{ $report->title }}  
@if ($report->evidence)
**Evidence/Comment:** {{ $report->evidence }}  
@endif
@if ($report->doi)
**DOI:** [{{ $report->doi }}](https://doi.org/{{ $report->doi }})  
@endif
@if ($report->comment)
**Comment:** {{ $report->comment }}  
@endif
**Status:** {{ ucwords(strtolower(str_replace('_', ' ', $report->status))) }}

@component('mail::button', ['url' => $url])
View Report
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
