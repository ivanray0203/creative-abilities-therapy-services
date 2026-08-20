@component('mail::message')
# Your Session Has Been Cancelled

Hello,

The following session for **{{ $session->client?->displayName() ?? 'your child' }}** has been cancelled.

@component('mail::table')
| | |
|:--|:--|
| **When** | {{ $session->scheduled_start?->format('l, j F Y \a\t g:i A') ?? 'Not recorded' }} |
| **Therapist** | {{ trim("{$session->therapist?->first_name} {$session->therapist?->last_name}") ?: 'Not recorded' }} |
| **Service** | {{ $session->service?->name ?? $session->service_name ?? 'Not recorded' }} |
@endcomponent

@if (filled($session->cancel_reason))
**Reason:** {{ $session->cancel_reason }}
@endif

@component('mail::button', ['url' => route('client.home')])
View My Calendar
@endcomponent

Please contact us to arrange a replacement session.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
