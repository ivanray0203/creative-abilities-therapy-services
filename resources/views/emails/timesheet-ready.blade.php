@component('mail::message')
# Timesheet {{ $timesheet->timesheet_number }}

{{ optional($timesheet->therapist)->full_name ?? 'Your aide' }} has completed a time sheet for
{{ optional($timesheet->client)->displayName() ?? 'your child' }} and signed it. It now needs your signature.

@component('mail::panel')
**Period:** {{ $timesheet->period_start?->format('j M Y') }} – {{ $timesheet->period_end?->format('j M Y') }}<br>
**Total hours:** {{ number_format((float) $timesheet->total_hours, 2) }}
@endcomponent

@component('mail::button', ['url' => url("/client/timesheets/{$timesheet->id}")])
Review and Sign
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
