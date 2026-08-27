<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 64px 64px 48px 64px; }
        body { font-family: Helvetica, sans-serif; font-size: 10.5pt; color: #1a1a1a; line-height: 1.5; }
        .logo { width: 1.6in; margin-bottom: 18px; }
        .letterhead { font-family: Helvetica-Bold, sans-serif; font-size: 12pt; letter-spacing: 0.4px; margin-bottom: 18px; }
        .date { font-size: 10pt; color: #444; margin-bottom: 4px; }
        .confidential { font-family: Helvetica-Bold, sans-serif; font-size: 9.5pt; letter-spacing: 0.6px; text-transform: uppercase; color: #666; margin-bottom: 18px; }
        .recipient { margin-bottom: 18px; }
        .recipient .name { font-family: Helvetica-Bold, sans-serif; }
        .re { font-family: Helvetica-Bold, sans-serif; margin: 0 0 14px 0; }
        p { margin: 0 0 11px 0; }
        .heading { font-family: Helvetica-Bold, sans-serif; font-size: 11.5pt; margin: 18px 0 7px 0; }
        table.details { width: 100%; border-collapse: collapse; margin: 4px 0 14px 0; }
        table.details td { padding: 4px 0; vertical-align: top; }
        table.details td.label { font-family: Helvetica-Bold, sans-serif; width: 1.9in; }
        ul { margin: 0 0 11px 0; padding-left: 20px; }
        li { margin-bottom: 5px; }
        .acceptance { margin-top: 26px; padding-top: 16px; border-top: 1.5px solid #333; page-break-inside: avoid; }
        .acceptance-title { font-family: Helvetica-Bold, sans-serif; font-size: 11.5pt; letter-spacing: 0.5px; margin-bottom: 10px; }
        table.signatures { width: 100%; border-collapse: collapse; margin-top: 26px; }
        table.signatures td { width: 50%; vertical-align: bottom; padding-right: 24px; }
        .sig-box { height: 62px; }
        .sig-box img { max-height: 58px; max-width: 2.6in; }
        .sig-pending { color: #999; font-size: 9.5pt; padding-top: 40px; }
        .sig-rule { border-top: 1px solid #333; padding-top: 4px; font-size: 9.5pt; }
    </style>
</head>
<body>
    @php($logoPath = public_path('CatsLogo/web-app-manifest-512x512.png'))
    @if (file_exists($logoPath))
        <img src="{{ $logoPath }}" class="logo">
    @endif

    <div class="letterhead">{{ Str::upper($legalName) }}</div>

    <p class="date">{{ $issuedDate->format('m-d-Y') }}</p>
    <p class="confidential">Private &amp; Confidential</p>

    <div class="recipient">
        <div class="name">{{ $candidateName }}</div>
        @foreach ($candidateAddress as $line)
            <div>{{ $line }}</div>
        @endforeach
    </div>

    <p>Dear {{ $candidateName }},</p>

    <p class="re">Re: Offer of {{ $engagementType }} Position</p>

    <p>
        On behalf of <strong>{{ $legalName }} (CATS)</strong>, I am pleased to offer you the
        position of <strong>{{ $application->position_applied }}</strong> as an
        <strong>{{ $engagementType }}</strong>. The details of this offer are as follows:
    </p>

    <table class="details">
        <tr>
            <td class="label">Position:</td>
            <td>{{ $application->position_applied }}</td>
        </tr>
        <tr>
            <td class="label">Service Area:</td>
            <td>{{ $serviceArea }}</td>
        </tr>
        <tr>
            <td class="label">Compensation:</td>
            <td>${{ number_format((float) $application->hourly_rate, 2) }}/hour</td>
        </tr>
        <tr>
            <td class="label">Proposed Start Date:</td>
            <td>{{ $startDate?->format('m-d-Y') ?? 'To be confirmed' }}</td>
        </tr>
        <tr>
            <td class="label">Location:</td>
            <td>{{ $location }}</td>
        </tr>
        <tr>
            <td class="label">Schedule:</td>
            <td>{{ $schedule }}</td>
        </tr>
        <tr>
            <td class="label">Reports To:</td>
            <td>{{ $reportsTo }}</td>
        </tr>
    </table>

    <p>This letter outlines the basic terms of your engagement with {{ $legalName }}.</p>

    <p class="heading">{{ $engagementType }} Status</p>
    <p>
        You will provide services as an {{ $engagementType }} and <strong>not</strong> as an employee
        of {{ $legalName }}. As such, you are responsible for your own income taxes, Canada Pension
        Plan (CPP) contributions, insurance, and any other statutory obligations.
    </p>

    <p class="heading">Professional Requirements</p>
    <p>
        Where applicable, you are responsible for maintaining your professional registration,
        liability insurance, and any license or certifications required to practice within your
        profession. You are also expected to comply with the standards of your regulatory college
        or professional association, as well as the policies and procedures of {{ $legalName }}.
    </p>

    <p class="heading">Conditions of Offer</p>
    <p>This offer is conditional upon:</p>
    <ul>
        <li>Completion of all required onboarding documentation.</li>
        <li>Signing the Independent Contractor Agreement.</li>
        <li>Submission of all required credentials and documentation applicable to your role.</li>
    </ul>

    <p class="heading">Acceptance</p>
    <p>
        Please indicate your acceptance of this offer by signing below and returning this document
        no later than <strong>{{ $deadline->format('m-d-Y') }}</strong>.
    </p>
    <p>
        We are excited about the opportunity to work with you and look forward to welcoming you to
        the CATS team.
    </p>

    <p>Sincerely,<br>{{ $legalName }}</p>

    <div class="acceptance">
        <div class="acceptance-title">ACCEPTANCE OF OFFER</div>
        <p>
            I, <strong>{{ $candidateName }}</strong>, acknowledge that I have read and understand
            this Offer Letter and accept the position of {{ $application->position_applied }} as an
            {{ $engagementType }} with {{ $legalName }}.
        </p>
        <p>
            I understand that the complete terms and conditions of my engagement are contained
            within the Independent Contractor Agreement.
        </p>

        <table class="signatures">
            <tr>
                <td>
                    <div class="sig-box">
                        @if ($signature)
                            <img src="{{ $signature }}" alt="">
                        @else
                            <div class="sig-pending">Awaiting signature</div>
                        @endif
                    </div>
                    <div class="sig-rule">Contractor Signature</div>
                </td>
                <td>
                    <div class="sig-box">
                        <div class="sig-pending" style="padding-top: 44px; color: #1a1a1a;">
                            {{ $signedAt?->format('m-d-Y') ?? '' }}
                        </div>
                    </div>
                    <div class="sig-rule">Date</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
