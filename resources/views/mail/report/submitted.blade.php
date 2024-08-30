<x-mail::message>

Hello {{ $user->name }},
Thank you for submitting your report. It is pending review with our Curators. You will reveive further updates via email.

### Report Details:
- **Report Title:** {{ $event->report->title }}
- **URL:** {{ $event->report->url }}
- **Evidence:** {{ $event->report->evidence }}


<x-mail::button :url="$url">
View Report
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
