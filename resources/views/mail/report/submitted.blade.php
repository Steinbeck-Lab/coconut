<x-mail::message>

Hello {{ $user->name }},

@if ($mail_to == 'owner')
Thank you for submitting your report. It is pending review with our Curators. You will reveive further updates via email.
@else
A report has been submitted. Please review the report and take necessary actions.
@endif

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
