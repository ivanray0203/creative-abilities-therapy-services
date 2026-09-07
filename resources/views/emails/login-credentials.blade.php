@component('mail::message')
# Welcome Aboard – Let's Get You Onboarded

Hi {{ $firstName }},

Congratulations on signing your offer! Your therapist portal account has been created so you can complete your onboarding.

@component('mail::panel')
**Email:** {{ $email }}<br>
@if ($password !== null)
**Temporary Password:** {{ $password }}
@else
**Password:** the password you already use for this email address
@endif
@endcomponent

@if ($password !== null)
Please log in and change your password as soon as possible.
@endif

## Documents we need from you

@if (count($requiredDocuments) > 0)
Before we can finalize your hire, please log in, open **Profile → Documents**, and upload each of the following:

@foreach ($requiredDocuments as $document)
- {{ $document }}
@endforeach
@else
Before we can finalize your hire, please log in, open **Profile → Documents**, and upload the documents your position requires.
@endif

Our team will review your documents and let you know by email once your hire is confirmed. Until then, only your profile is available in the portal.

@component('mail::button', ['url' => url('/login')])
Log In
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
