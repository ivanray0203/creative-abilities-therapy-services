@component('mail::message')
# New Intake Assigned for Review

Hi {{ $therapistFirstName }},

@if (! empty($services))
An intake for **{{ $intake->child_first_name }} {{ $intake->child_last_name }}** ({{ $intake->reference_number }}) has been assigned to you for review for the following service(s): **{{ implode(', ', $services) }}**.
@else
An intake for **{{ $intake->child_first_name }} {{ $intake->child_last_name }}** ({{ $intake->reference_number }}) has been assigned to you for review.
@endif

@component('mail::button', ['url' => url("/therapist/intake/{$intake->id}")])
Review Intake
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
