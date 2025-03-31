<x-mail::message>
# Report Status Update Notification

Hello {{ $user->name }},

@if ($mail_to == 'owner')
The status of your report titled "{{ $event->report->title }}" has been changed to "{{ $event->report->status }}".

Please review the updated status and feel free to reach out if you have any questions.
@else
The status of the report titled "{{ $event->report->title }}" has been changed to "{{ $event->report->status }}".

Please review the updated status and take necessary actions.
@endif

<x-mail::button :url="$url">
View Report
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
