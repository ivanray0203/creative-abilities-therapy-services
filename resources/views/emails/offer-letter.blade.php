@component('mail::message')
# Offer Letter

Hi {{ $firstName }} {{ $lastName }},

Congratulations! Please find your offer letter for the **{{ $position }}** position attached to this email.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
