@component('mail::message')
@if ($replaced)
# Signed Consent Replaced

A parent has replaced their earlier upload with a corrected signed Therapy Consent and Family
Service Agreement. The earlier files have been removed.
@else
# Signed Consent Returned

A parent has uploaded their signed Therapy Consent and Family Service Agreement.
@endif

@component('mail::table')
| | |
|:--|:--|
| **Client** | {{ $client->displayName() }} |
| **Parent** | {{ $client->originalIntake?->primary_parent_name ?? '—' }} |
| **Received** | {{ $client->consent_signed_at?->format('j F Y') ?? now()->format('j F Y') }} |
@endcomponent

The signed copy is on the client's Documents tab.

@component('mail::button', ['url' => url("/admin/clients/{$client->id}")])
Open Client
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
