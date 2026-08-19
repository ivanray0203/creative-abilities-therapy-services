@component('mail::message')
# Session {{ ucfirst($action) }}

A session was {{ $action }} by **{{ trim("{$session->therapist?->first_name} {$session->therapist?->last_name}") ?: 'an unknown user' }}**.

@component('mail::table')
| | |
|:--|:--|
| **Session** | #{{ $session->id }} |
| **Client** | {{ $session->client?->displayName() ?? 'Unknown client' }} |
| **Therapist** | {{ trim("{$session->therapist?->first_name} {$session->therapist?->last_name}") ?: 'Unassigned' }} |
| **Service** | {{ $session->service?->name ?? $session->service_name ?? 'Not set' }} |
@if ($previousStart !== null)
| **Previous start** | {{ $previousStart->format('l, j F Y \a\t g:i A') }} |
@endif
| **{{ $action === 'cancelled' ? 'Was scheduled for' : 'Scheduled for' }}** | {{ $session->scheduled_start?->format('l, j F Y \a\t g:i A') ?? 'Not set' }} |
| **Duration** | {{ $session->duration ? "{$session->duration} minutes" : 'Not set' }} |
| **Location** | {{ $session->location ?: 'Not set' }} |
| **Status** | {{ ucfirst($session->status ?? 'unknown') }} |
@endcomponent

@if ($action === 'cancelled' && filled($session->cancel_reason))
**Cancellation reason:** {{ $session->cancel_reason }}
@endif

@component('mail::button', ['url' => route('admin.sessions.index')])
Open Sessions
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
