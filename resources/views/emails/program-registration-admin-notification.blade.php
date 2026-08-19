@component('mail::message')
# New Program Registration

A family has registered a child for **{{ $registration->program?->name ?? 'a program' }}**.

@component('mail::table')
| | |
|:--|:--|
| **Reference** | {{ $registration->reference_number }} |
| **Program** | {{ $registration->program?->name ?? 'Not recorded' }} |
| **Participant** | {{ $registration->participantName() }} |
| **Date of birth** | {{ $registration->participant_date_of_birth?->format('j F Y') ?? 'Not provided' }} |
| **Parent / caregiver** | {{ $registration->parent_name }} |
| **Email** | {{ $registration->parent_email }} |
| **Phone** | {{ $registration->parent_phone }} |
@endcomponent

@if (filled($registration->notes))
**Notes from the family:** {{ $registration->notes }}
@endif

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
