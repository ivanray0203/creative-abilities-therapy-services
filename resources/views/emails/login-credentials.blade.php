@component('mail::message')
# Your CATS Account is Ready

Hi {{ $firstName }},

Welcome to the team! Your therapist portal account has been created.

@component('mail::panel')
**Email:** {{ $email }}<br>
**Temporary Password:** {{ $password }}
@endcomponent

Please log in and change your password as soon as possible.

@component('mail::button', ['url' => url('/login')])
Log In
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
