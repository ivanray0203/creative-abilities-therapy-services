@component('mail::message')
# New Contact Form Submission

**Name:** {{ $contact->contact['name'] ?? '' }}<br>
**Email:** {{ $contact->contact['email'] ?? '' }}<br>
**Phone:** {{ $contact->contact['phone'] ?? '-' }}<br>
**Subject:** {{ $contact->contact['subject'] ?? '' }}

@component('mail::panel')
{{ $contact->contact['message'] ?? '' }}
@endcomponent

{{ config('cats.name') }}
@endcomponent
