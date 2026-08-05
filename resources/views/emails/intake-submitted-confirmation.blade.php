@component('mail::message')
# Intake Form Received

Hi {{ $intake->primary_parent_name }},

Thank you for submitting an intake form for **{{ $intake->child_first_name }} {{ $intake->child_last_name }}**. Your reference number is **{{ $intake->reference_number }}**.

Our team will review your submission and be in touch soon.

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
