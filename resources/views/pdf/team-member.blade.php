<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 54px 60px; }
        body { font-family: Helvetica, sans-serif; font-size: 10.5pt; color: #1a1a1a; }
        .title { font-family: Helvetica-Bold, sans-serif; font-size: 18pt; margin: 0 0 2px; }
        .subtitle { color: #555; font-size: 10pt; margin: 0 0 2px; }
        .meta { color: #777; font-size: 8.5pt; margin: 0 0 18px; }
        .section-title {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 11.5pt;
            color: #D87E45;
            border-bottom: 1px solid #d4d4d4;
            padding-bottom: 3px;
            margin: 18px 0 8px;
        }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 3px 0; }
        td.label { color: #555; width: 34%; }
        td.value { width: 66%; }
        table.grid td { border-bottom: 1px solid #ececec; padding: 5px 6px; }
        table.grid th {
            font-family: Helvetica-Bold, sans-serif;
            text-align: left;
            border-bottom: 1px solid #d4d4d4;
            padding: 5px 6px;
            color: #555;
        }
        .muted { color: #888; }
        .notes { line-height: 1.5; }
    </style>
</head>
<body>
    <p class="title">{{ $fullName }}</p>
    <p class="subtitle">{{ $teamMember->position ?? 'Team Member' }}{{ $teamMember->department ? ' · '.Str::headline($teamMember->department) : '' }}</p>
    <p class="meta">{{ config('cats.name') }} &middot; Generated {{ $generatedAt->format('F j, Y \a\t g:i A') }}</p>

    <p class="section-title">Personal Information</p>
    <table>
        <tr><td class="label">Employee ID</td><td class="value">{{ $teamMember->id }}</td></tr>
        <tr><td class="label">First Name</td><td class="value">{{ $teamMember->user?->first_name ?? '—' }}</td></tr>
        <tr><td class="label">Last Name</td><td class="value">{{ $teamMember->user?->last_name ?? '—' }}</td></tr>
        <tr><td class="label">Birthdate</td><td class="value">{{ $teamMember->birthdate?->format('F j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Resident Status</td><td class="value">{{ $teamMember->resident_status ?? '—' }}</td></tr>
        {{-- SIN is stored one-way hashed and is deliberately never printed. --}}
        <tr><td class="label">SIN</td><td class="value muted">Not disclosed</td></tr>
    </table>

    <p class="section-title">Contact</p>
    <table>
        <tr><td class="label">Email</td><td class="value">{{ $teamMember->user?->email ?? '—' }}</td></tr>
        <tr><td class="label">Secondary Email</td><td class="value">{{ $teamMember->secondary_email ?: '—' }}</td></tr>
        <tr><td class="label">Phone</td><td class="value">{{ $teamMember->phone ?: '—' }}</td></tr>
        <tr><td class="label">Office Phone</td><td class="value">{{ $teamMember->office_phone ?: '—' }}</td></tr>
        <tr><td class="label">Address</td><td class="value">{{ $address ?: '—' }}</td></tr>
    </table>

    <p class="section-title">Employment</p>
    <table>
        <tr><td class="label">Position</td><td class="value">{{ $teamMember->position ?: '—' }}</td></tr>
        <tr><td class="label">Department</td><td class="value">{{ $teamMember->department ? Str::headline($teamMember->department) : '—' }}</td></tr>
        <tr><td class="label">Employment Status</td><td class="value">{{ $teamMember->employment_status ? Str::headline($teamMember->employment_status) : '—' }}</td></tr>
        <tr><td class="label">Hire Date</td><td class="value">{{ $teamMember->hire_date?->format('F j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Hourly Rate</td><td class="value">{{ $teamMember->hourly_rate !== null ? '$'.number_format((float) $teamMember->hourly_rate, 2) : '—' }}</td></tr>
        <tr><td class="label">Maximum Caseload</td><td class="value">{{ $teamMember->maximum_caseload ?? '—' }}</td></tr>
        <tr><td class="label">License Number</td><td class="value">{{ $teamMember->license_number ?: '—' }}</td></tr>
        <tr><td class="label">Years of Experience</td><td class="value">{{ $teamMember->years_of_experience ?? '—' }}</td></tr>
        <tr><td class="label">Account Active</td><td class="value">{{ $teamMember->user?->is_active ? 'Yes' : 'No' }}</td></tr>
    </table>

    <p class="section-title">Weekly Availability</p>
    @if ($availability->isEmpty())
        <p class="muted">No availability recorded.</p>
    @else
        <table class="grid">
            <thead>
                <tr><th>Day</th><th>From</th><th>To</th></tr>
            </thead>
            <tbody>
                @foreach ($availability as $slot)
                    <tr>
                        <td>{{ $slot['week_day'] ?? '—' }}</td>
                        <td>{{ $slot['time_from'] ?? '—' }}</td>
                        <td>{{ $slot['time_to'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="section-title">Credentials</p>
    @if (empty($teamMember->credentials))
        <p class="muted">None recorded.</p>
    @else
        <p>{{ implode(', ', $teamMember->credentials) }}</p>
    @endif

    <p class="section-title">Specializations</p>
    @if (empty($teamMember->specializations))
        <p class="muted">None recorded.</p>
    @else
        <p>{{ implode(', ', $teamMember->specializations) }}</p>
    @endif

    <p class="section-title">Emergency Contact</p>
    <table>
        <tr><td class="label">Name</td><td class="value">{{ $teamMember->emergency_contact_name ?: '—' }}</td></tr>
        <tr><td class="label">Phone</td><td class="value">{{ $teamMember->emergency_contact_phone ?: '—' }}</td></tr>
    </table>

    <p class="section-title">Access Permissions</p>
    <table>
        <tr><td class="label">Finance Access</td><td class="value">{{ $teamMember->can_access_finance ? 'Granted' : 'Not granted' }}</td></tr>
        <tr><td class="label">Team Management</td><td class="value">{{ $teamMember->can_manage_team ? 'Granted' : 'Not granted' }}</td></tr>
        <tr><td class="label">Client Management</td><td class="value">{{ $teamMember->can_manage_clients ? 'Granted' : 'Not granted' }}</td></tr>
    </table>

    <p class="section-title">Documents</p>
    @if ($documents->isEmpty())
        <p class="muted">No documents on file.</p>
    @else
        <table class="grid">
            <thead>
                <tr><th>Name</th><th>Type</th><th>Uploaded</th></tr>
            </thead>
            <tbody>
                @foreach ($documents as $document)
                    <tr>
                        <td>{{ $document['name'] ?? '—' }}</td>
                        <td>{{ $document['type'] ?? '—' }}</td>
                        <td>{{ $document['uploaded_at'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="section-title">Additional Notes</p>
    <p class="notes">{{ $teamMember->additional_notes ?: '—' }}</p>
</body>
</html>
