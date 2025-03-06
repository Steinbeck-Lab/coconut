<x-mail::message>
# Report Assigned Notification

Hello {{ $curator->name }},

As a COCONUT curator, you have been assigned a report titled "{{ $event->report->title }}".

Please review the report and leave your comments1.

<x-mail::button :url="$url">
To the Report
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
