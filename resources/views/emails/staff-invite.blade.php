@component('mail::message')
# Your Creative Abilities Therapy Account

Hi {{ $firstName }},

An account has been created for you.

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
