@component('mail::message')
# Application Received

Hi {{ $application->first_name }},

Thank you for applying for the **{{ $application->position_applied }}** position. Your reference number is **{{ $application->reference_number }}**.

Our team will review your application and be in touch soon.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
