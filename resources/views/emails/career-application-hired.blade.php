@component('mail::message')
# You're Hired!

Hi {{ $application->first_name }},

We have reviewed your onboarding documents and are delighted to confirm you have been hired for the **{{ $application->position_applied }}** position.

Your therapist portal is now fully unlocked. Log in with the same email and password to see your calendar, sessions, clients, and everything else you need to get started.

@component('mail::button', ['url' => url('/login')])
Log In
@endcomponent

Welcome to the team!

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
