<x-mail::message>
# Report Status Update Notification

Hello {{ $user->name }},

This email is to inform you about the recent status update on the report titled "{{ $event->report->title }}".
- **Status Update:** The status of the report has changed from "{{ $event->old }}" to "{{ $event->new }}"
- **Curator Comments:** {{ $event->report->comment }}

### Report Details:
- **Report Title:** {{ $event->report->title }}
- **URL:** {{ $event->report->url }}
- **Evidence:** {{ $event->report->evidence }}


Please review the updated status and feel free to reach out if you have any questions.

<x-mail::button :url="$url">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
