@component('mail::message')
# Your Session Has Been Scheduled

Hello,

A new session has been booked for **{{ $session->client?->displayName() ?? 'your child' }}**.

@component('mail::table')
| | |
|:--|:--|
| **When** | {{ $session->scheduled_start?->format('l, j F Y \a\t g:i A') ?? 'To be confirmed' }} |
| **Duration** | {{ $session->duration ? "{$session->duration} minutes" : 'To be confirmed' }} |
| **Therapist** | {{ trim("{$session->therapist?->first_name} {$session->therapist?->last_name}") ?: 'To be confirmed' }} |
| **Service** | {{ $session->service?->name ?? $session->service_name ?? 'To be confirmed' }} |
| **Location** | {{ $session->location ?: 'To be confirmed' }} |
@endcomponent

@component('mail::button', ['url' => route('client.home')])
View My Calendar
@endcomponent

If this time does not work for you, please contact us and we will rearrange it.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
