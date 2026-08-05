@component('mail::message')
# New Career Application Received

A new application was submitted for **{{ $application->position_applied }}** by {{ $application->first_name }} {{ $application->last_name }} ({{ $application->email }}). Reference: {{ $application->reference_number }}.

@component('mail::button', ['url' => url("/admin/applications/{$application->id}")])
Review Application
@endcomponent
@endcomponent
