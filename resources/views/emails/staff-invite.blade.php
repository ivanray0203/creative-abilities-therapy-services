@component('mail::message')
# Your Creative Abilities Therapy Account

Hi {{ $firstName }},

An account has been created for you.

@component('mail::panel')
**Email:** {{ $email }}<br>
**Temporary Password:** {{ $password }}
@endcomponent

**Before logging in, please watch the attached demo video and follow the steps it shows.** It walks you through the portal and what to do on your first sign-in.

Once you have followed the demo, log in with the credentials above and change your password as soon as possible.

@component('mail::button', ['url' => url('/login')])
Log In
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
