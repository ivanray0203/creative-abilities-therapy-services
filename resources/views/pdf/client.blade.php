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
        .tab-title {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 13pt;
            color: #1f5673;
            border-bottom: 2px solid #1f5673;
            padding-bottom: 4px;
            margin: 22px 0 4px;
        }
        .section-title {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 11pt;
            border-bottom: 1px solid #d4d4d4;
            padding-bottom: 3px;
            margin: 14px 0 6px;
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
        .note { border: 1px solid #ececec; padding: 8px; margin-bottom: 6px; }
        .note-meta { color: #777; font-size: 9pt; margin: 0 0 4px; }
        .note-body { line-height: 1.5; margin: 0; }
        .service-heading { font-family: Helvetica-Bold, sans-serif; margin: 12px 0 4px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <p class="title">{{ $childName }}</p>
    <p class="subtitle">Client #{{ $client->id }} &middot; {{ $client->status ? Str::headline($client->status) : 'No status' }}</p>
    <p class="meta">{{ config('cats.name') }} &middot; Generated {{ $generatedAt->format('F j, Y \a\t g:i A') }}</p>

    {{-- ------------------------------------------------------- Overview --}}
    <p class="tab-title">Overview</p>

    <p class="section-title">Child Information</p>
    <table>
        <tr><td class="label">Full Name</td><td class="value">{{ $childName }}</td></tr>
        <tr><td class="label">Gender</td><td class="value">{{ $intake?->gender ? Str::headline($intake->gender) : '—' }}</td></tr>
        <tr><td class="label">Date of Birth</td><td class="value">{{ $intake?->date_of_birth?->format('F j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Age</td><td class="value">{{ $intake?->age !== null ? $intake->age.' years old' : '—' }}</td></tr>
        <tr><td class="label">Approved</td><td class="value">{{ $client->approved_date?->format('F j, Y') ?? '—' }}</td></tr>
    </table>

    <p class="section-title">Assigned Therapist</p>
    @if ($client->assignedTherapist)
        <table>
            <tr><td class="label">Name</td><td class="value">{{ $client->assignedTherapist->full_name }}</td></tr>
            <tr><td class="label">Email</td><td class="value">{{ $client->assignedTherapist->email ?: '—' }}</td></tr>
            <tr><td class="label">Assigned</td><td class="value">{{ $client->assigned_at?->format('F j, Y') ?? '—' }}</td></tr>
        </table>
    @else
        <p class="muted">No therapist assigned yet.</p>
    @endif

    <p class="section-title">Primary Parent / Guardian</p>
    <table>
        <tr><td class="label">Name</td><td class="value">{{ $intake?->primary_parent_name ?: '—' }}</td></tr>
        <tr><td class="label">Email</td><td class="value">{{ $intake?->primary_parent_email ?: '—' }}</td></tr>
        <tr><td class="label">Phone</td><td class="value">{{ $intake?->primary_parent_phone ?: '—' }}</td></tr>
        <tr><td class="label">Relationship</td><td class="value">{{ $intake?->primary_relationship_to_child ?: '—' }}</td></tr>
    </table>

    <p class="section-title">Emergency Contact</p>
    <table>
        <tr><td class="label">Name</td><td class="value">{{ $intake?->emergency_contact_name ?: '—' }}</td></tr>
        <tr><td class="label">Relationship</td><td class="value">{{ $intake?->emergency_contact_relationship ? Str::headline($intake->emergency_contact_relationship) : '—' }}</td></tr>
        <tr><td class="label">Phone</td><td class="value">{{ $intake?->emergency_contact_phone ?: '—' }}</td></tr>
    </table>

    <p class="section-title">Medical History</p>
    <table>
        <tr><td class="label">Diagnosis</td><td class="value">{{ filled($intake?->diagnosis) ? implode(', ', $intake->diagnosis) : '—' }}</td></tr>
        <tr><td class="label">Medical Conditions</td><td class="value">{{ $intake?->medical_conditions ?: '—' }}</td></tr>
        <tr><td class="label">Allergies</td><td class="value">{{ filled($client->allergies) ? implode(', ', $client->allergies) : '—' }}</td></tr>
    </table>

    <p class="section-title">Current Services</p>
    @if ($services->isEmpty())
        <p class="muted">No services availed yet.</p>
    @else
        <table class="grid">
            <thead>
                <tr><th>Service</th><th>Therapist</th><th>Frequency</th><th>Duration</th><th>Start</th></tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td>{{ $service->service?->name ?? 'Service' }}</td>
                        <td>{{ $service->therapist?->full_name ?? '—' }}</td>
                        <td>{{ $service->frequency ?: '—' }}</td>
                        <td>{{ $service->duration ?: '—' }}</td>
                        <td>{{ $service->start_date?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($requestedServices->isNotEmpty())
        <p class="section-title">Requested (not yet availed)</p>
        <p>{{ $requestedServices->implode(', ') }}</p>
    @endif

    <p class="section-title">Availability</p>
    <table>
        <tr><td class="label">Available Days</td><td class="value">{{ filled($intake?->available_days) ? implode(', ', $intake->available_days) : '—' }}</td></tr>
        <tr><td class="label">Preferred Times</td><td class="value">{{ filled($intake?->preferred_times) ? implode(', ', $intake->preferred_times) : '—' }}</td></tr>
    </table>

    {{-- ------------------------------------------------------- Sessions --}}
    <p class="tab-title page-break">Sessions</p>

    @if ($sessions->isEmpty())
        <p class="muted">No sessions scheduled yet.</p>
    @else
        <table class="grid">
            <thead>
                <tr><th>Date</th><th>Time</th><th>Services</th><th>Therapist</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach ($sessions as $session)
                    <tr>
                        <td>{{ $session->scheduled_start?->format('M j, Y') ?? '—' }}</td>
                        <td>{{ $session->scheduled_start?->format('g:i A') ?? '—' }}</td>
                        <td>
                            {{ $session->clientServices->map(fn ($linked) => $linked->service?->name)->filter()->implode(', ')
                                ?: ($session->service?->name ?? '—') }}
                        </td>
                        <td>{{ $session->therapist?->full_name ?? '—' }}</td>
                        <td>{{ Str::headline($session->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- -------------------------------------------------------- Funding --}}
    <p class="tab-title">Funding</p>

    <p class="section-title">Funding Details</p>
    <table>
        <tr><td class="label">Funding Source</td><td class="value">{{ $intake?->funding_source ?: '—' }}</td></tr>
        @foreach ($fundingDetails as $label => $value)
            <tr><td class="label">{{ $label }}</td><td class="value">{{ $value ?: '—' }}</td></tr>
        @endforeach
    </table>

    <p class="section-title">Contract Dates</p>
    <table>
        <tr><td class="label">Contract Start</td><td class="value">{{ $client->contract_start_date?->format('F j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Contract End</td><td class="value">{{ $client->contract_end_date?->format('F j, Y') ?? '—' }}</td></tr>
        <tr><td class="label">Signed</td><td class="value">{{ $client->signed_date?->format('F j, Y') ?? '—' }}</td></tr>
    </table>

    {{-- ---------------------------------------------------------- Notes --}}
    <p class="tab-title">Clinical Notes</p>

    @if ($notes->isEmpty())
        <p class="muted">No notes recorded.</p>
    @else
        @foreach ($notes as $note)
            <div class="note">
                <p class="note-meta">{{ $note['user'] ?? 'Unknown' }} &middot; {{ $note['date'] ?? '' }} {{ $note['time'] ?? '' }}</p>
                <p class="note-body">{{ $note['note'] ?? '' }}</p>
            </div>
        @endforeach
    @endif

    {{-- ------------------------------------------------------ Therapist --}}
    <p class="tab-title">Care Team</p>

    @if ($careTeam->isEmpty())
        <p class="muted">No therapists on the care team.</p>
    @else
        <table class="grid">
            <thead>
                <tr><th>Therapist</th><th>Email</th><th>Role</th></tr>
            </thead>
            <tbody>
                @foreach ($careTeam as $therapist)
                    <tr>
                        <td>{{ $therapist->full_name }}</td>
                        <td>{{ $therapist->email ?: '—' }}</td>
                        <td>{{ $therapist->id === $client->primary_therapist_id ? 'Primary Therapist' : 'Care Team' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
