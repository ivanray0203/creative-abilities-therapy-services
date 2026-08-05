@component('mail::message')
# Welcome to CATS

Hi {{ $firstName }},

Your intake has been approved and a client portal account has been created for you.

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
