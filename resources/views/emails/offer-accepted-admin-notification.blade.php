@component('mail::message')
# {{ $declined ? 'Offer Declined' : 'Offer Accepted' }}

{{ trim("{$application->first_name} {$application->last_name}") }}
{{ $declined ? 'has declined the offer for' : 'has signed and accepted the offer for' }}
**{{ $application->position_applied }}**.

@component('mail::table')
| | |
|:--|:--|
| **Candidate** | {{ trim("{$application->first_name} {$application->last_name}") }} |
| **Email** | {{ $application->email }} |
| **Position** | {{ $application->position_applied }} |
| **Reference** | {{ $application->reference_number }} |
| **{{ $declined ? 'Declined' : 'Signed' }}** | {{ ($declined ? $application->offer_declined_at : $application->offer_accepted_at)?->format('l, j F Y \a\t g:i A') }} |
@endcomponent

@if (! $declined)
The signed letter is on file. You can now hire this candidate, which creates their account and
sends their login details.

@component('mail::button', ['url' => route('admin.applications.show', $application)])
Open the Application
@endcomponent
@endif

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
