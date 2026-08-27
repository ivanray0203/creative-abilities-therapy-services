@component('mail::message')
# Update on Your Application

Hi {{ $application->first_name }},

Thank you for your interest in the **{{ $application->position_applied }}** position, and for the time you put into your application.

After careful consideration, we will not be moving forward with your application on this occasion.

We appreciate you thinking of us, and we would be glad to hear from you again about a future opening.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
