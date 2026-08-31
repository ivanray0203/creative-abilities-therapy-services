@component('mail::message')
# Signed Timesheet Returned

A parent has signed and returned an aide's time sheet.

@component('mail::table')
| | |
|:--|:--|
| **Timesheet** | {{ $timesheet->timesheet_number }} |
| **Aide** | {{ optional($timesheet->therapist)->full_name ?? 'Unknown aide' }} |
| **Client** | {{ optional($timesheet->client)->displayName() ?? 'Unknown client' }} |
| **Period** | {{ $timesheet->period_start?->format('j M Y') }} – {{ $timesheet->period_end?->format('j M Y') }} |
| **Total hours** | {{ number_format((float) $timesheet->total_hours, 2) }} |
@endcomponent

The signed copy has been filed on Drive under `Timesheet/signed`.

@component('mail::button', ['url' => url("/admin/timesheets/{$timesheet->id}")])
Open Timesheet
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
