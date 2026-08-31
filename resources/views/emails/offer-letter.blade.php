@component('mail::message')
# Your Offer from {{ config('cats.name') }}

Hi {{ $application->first_name }},

We are pleased to offer you the position of **{{ $application->position_applied }}** as an
{{ config('cats.offer.engagement_type') }}. Your offer letter is attached, and you can read and
sign it online using the button below.

@component('mail::table')
| | |
|:--|:--|
| **Position** | {{ $application->position_applied }} |
| **Compensation** | ${{ number_format((float) $application->hourly_rate, 2) }}/hour |
| **Proposed start** | {{ $application->preferred_start_date?->format('F j, Y') ?? 'To be confirmed' }} |
| **Sign by** | {{ $application->offer_expires_at?->format('F j, Y') ?? 'To be confirmed' }} |
@endcomponent

@component('mail::button', ['url' => $signingUrl])
Read and Sign Your Offer
@endcomponent

This link is personal to you and stops working after
**{{ $application->offer_expires_at?->format('F j, Y') ?? 'the date above' }}**. If we have not
heard from you by then, we will assume you are no longer interested in the position.

If you have any questions before signing, just reply to this email.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
