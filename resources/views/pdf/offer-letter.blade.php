<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 72px 72px 48px 72px; }
        body { font-family: Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; }
        .logo { width: 1.75in; margin-bottom: 24px; }
        .title { font-family: Helvetica-Bold, sans-serif; font-size: 18pt; margin-bottom: 6px; }
        .date { font-size: 10pt; color: #444; margin-bottom: 18px; }
        p { line-height: 1.5; margin: 0 0 12px 0; }
        .heading { font-family: Helvetica-Bold, sans-serif; font-size: 13pt; margin-top: 18px; margin-bottom: 8px; }
        .signature-line { margin-top: 36px; border-top: 1px solid #333; width: 3in; padding-top: 4px; font-size: 10pt; }
    </style>
</head>
<body>
    @php($logoPath = public_path('CatsLogo/web-app-manifest-512x512.png'))
    @if (file_exists($logoPath))
        <img src="{{ $logoPath }}" class="logo">
    @endif

    <p class="title">OFFER LETTER</p>
    <p class="date">{{ $issuedDate->format('F j, Y') }}</p>

    <p>Dear {{ trim("{$application->first_name} {$application->last_name}") }},</p>

    <p>
        We are pleased to offer you the position of <strong>{{ $application->position_applied }}</strong>
        at Creative Abilities Therapy Services, at an hourly rate of
        <strong>${{ number_format((float) $application->hourly_rate, 2) }}</strong>.
    </p>

    <p>
        Please confirm your acceptance of this offer by
        <strong>{{ $deadline->format('F j, Y') }}</strong>. If we do not hear from you by this date,
        we will assume you are no longer interested in this position.
    </p>

    <p class="heading">Acceptance of Offer</p>
    <p>
        I, {{ trim("{$application->first_name} {$application->last_name}") }}, accept the position of
        {{ $application->position_applied }} as described above.
    </p>

    <div class="signature-line">Signature</div>
    <div class="signature-line">Date</div>
</body>
</html>
