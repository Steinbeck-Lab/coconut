<x-mail::message>
# Report Status Update Notification

Hello {{ $user->name }},

This email is to inform you about the recent status update on the report titled "{{ $event->report->title }}".
- The status of the report has changed to "{{ $event->new }}"

Please review the updated status and feel free to reach out if you have any questions.

<x-mail::button :url="$url">
View Report
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
