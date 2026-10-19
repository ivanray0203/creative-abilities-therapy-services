@component('mail::message')
# Your Therapy Consent Is Ready to Sign

Hi {{ $client->originalIntake?->primary_parent_name ?? 'there' }},

Good news: every service requested for **{{ $client->displayName() }}** has been approved. Before
sessions begin, we need a signed copy of the Therapy Consent Form and Family Service Agreement.

1. Open the attached PDF (or download it from the page below).
2. Print it, then sign and date each signature line.
3. Scan or photograph the signed pages and upload them using the button below.

@component('mail::button', ['url' => $uploadUrl])
Upload Your Signed Consent
@endcomponent

This link is personal to you and works for {{ config('cats.consent.link_days') }} days. If it
stops working, just reply to this email and we will send a new one.

If you have any questions before signing, just reply to this email.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
