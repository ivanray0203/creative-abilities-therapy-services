@component('mail::message')
# Your Application Is Under Review

Hi {{ $application->first_name }},

Your application for the **{{ $application->position_applied }}** position is now with our hiring team.

Your reference number is **{{ $application->reference_number }}**. There is nothing you need to do right now — we will be in touch as soon as we have an update.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
