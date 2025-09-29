<x-mail::message>

Hello {{ $user->name }},

@if ($mail_to == 'owner')
Thank you for submitting your {{ $event->report->is_change ? 'Change Request' : 'Report' }}. It is pending review with our Curators. You will receive further updates via email.
@else
A {{ $event->report->is_change ? 'Change Request' : 'Report' }} has been submitted. Please review and take necessary actions.
@endif

### Details:
- **Title:** {{ $event->report->title }}
- **URL:** {{ $event->report->url }}
- **Evidence:** {{ $event->report->evidence }}


<x-mail::button :url="$url">
View {{ $event->report->is_change ? 'Change Request' : 'Report' }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
