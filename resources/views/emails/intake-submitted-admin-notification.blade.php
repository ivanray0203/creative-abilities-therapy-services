@component('mail::message')
# New Intake Form Submission

A new intake application has been submitted and is ready for review.

**Reference Number:** {{ $intake->reference_number }}

## Child Information

- **Name:** {{ $intake->child_first_name }} {{ $intake->child_last_name }}
- **Date of Birth:** {{ $intake->date_of_birth?->format('M j, Y') ?? 'Not provided' }}
- **Age:** {{ $intake->age ?? 'Not provided' }}
- **Gender:** {{ $intake->gender ?? 'Not provided' }}
- **Street Address:** {{ $intake->street_address ?? 'Not provided' }}
@if ($intake->address_line_2)
- **Address Line 2:** {{ $intake->address_line_2 }}
@endif
- **City / Province:** {{ $intake->city ?? 'Not provided' }}, {{ $intake->state_province ?? 'Not provided' }}
- **Full Address:** {{ collect([$intake->street_address, $intake->address_line_2, $intake->city, $intake->state_province, $intake->postal_code])->filter()->implode(', ') ?: 'Not provided' }}

@if (! empty($intake->services_needed))
**Services Requested:** {{ implode(', ', $intake->services_needed) }}
@endif

## Primary Contact

- **Name:** {{ $intake->primary_parent_name }}
- **Relationship:** {{ $intake->primary_relationship_to_child }}
- **Email:** {{ $intake->primary_parent_email }}
- **Phone:** {{ $intake->primary_parent_phone }}
- **Preferred Contact Method:** {{ $intake->primary_contact_method }}

@if ($intake->referral_source)
**Referral Source:** {{ $intake->referral_source }}
@endif

@if ($intake->additional_information)
## Additional Information

{{ $intake->additional_information }}
@endif

@component('mail::button', ['url' => url("/admin/intake/{$intake->id}")])
Review Intake
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
