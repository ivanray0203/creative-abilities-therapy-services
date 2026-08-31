@php
    // Phase 21 — the clinic's hour-tracking sheet, rendered from the contracts
    // and the session ledger instead of kept by hand.
    //
    // One section per service, one row per contract, a column per month in the
    // window. The month cells and the remaining figure come from the same
    // ledger rows, so a row always adds up.
    $months = $report['months'] ?? [];
    $sections = $report['sections'] ?? [];
    $totals = $report['totals'] ?? ['allotted_hours' => 0, 'used_hours' => 0, 'remaining_hours' => 0];

    // Trims a trailing .00 so 40 hours does not print as "40.00".
    $hours = function ($value) {
        if ($value === null) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0';
    };

    $sheetDate = function (?string $date) {
        return blank($date) ? '-' : \Illuminate\Support\Carbon::parse($date)->format('Y-M-d');
    };

    // The year only needs saying when the window crosses one.
    $spansYears = collect($months)->pluck('year')->unique()->count() > 1;

    // The clinic-wide sheet names the therapist on every row; a single
    // therapist's copy would just repeat their own name down the page.
    $showTherapist = $showTherapist ?? false;

    // Section and total rows span the columns before the hours figures.
    $leadColumns = $showTherapist ? 5 : 4;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 26px; }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 7.5pt;
            color: #1a1a1a;
        }

        table { border-collapse: collapse; width: 100%; }

        .head-logo { width: 62px; vertical-align: top; }
        .head-logo img { width: 58px; }

        .head-brand {
            vertical-align: top;
            padding-top: 4px;
            font-size: 11pt;
            font-family: Helvetica-Bold, sans-serif;
            line-height: 1.25;
        }
        .head-brand .brand-kicker {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 6.5pt;
            color: #D87E45;
        }
        .brand-accent { color: #D87E45; }

        .head-title {
            text-align: right;
            vertical-align: top;
            font-family: Helvetica-Bold, sans-serif;
            font-size: 13pt;
            letter-spacing: 0.5px;
        }
        .head-meta {
            text-align: right;
            font-size: 7.5pt;
            color: #555;
            padding-top: 3px;
        }

        .sheet { margin-top: 4px; }

        .sheet th {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 6.8pt;
            background: #F3EDE7;
            border: 0.5px solid #C9BCB1;
            padding: 4px 3px;
            text-align: center;
        }
        .sheet th.left { text-align: left; }

        .sheet td {
            border: 0.5px solid #D8CFC6;
            padding: 3px;
            text-align: center;
        }
        .sheet td.left { text-align: left; }

        .col-client { width: 15%; }
        .col-therapist { width: 11%; }
        .col-date { width: 8%; }
        .col-code { width: 6%; }
        .col-hours { width: 6%; }

        .section-row td {
            background: #E9E0D7;
            font-family: Helvetica-Bold, sans-serif;
            text-align: left;
            font-size: 8pt;
        }

        .total-row td {
            font-family: Helvetica-Bold, sans-serif;
            background: #F7F3EF;
        }

        .over { color: #B3261E; }
        .muted { color: #8A8A8A; }

        .legend {
            margin: 10px 0 0;
            font-size: 6.8pt;
            color: #777;
        }

        .empty {
            margin-top: 40px;
            text-align: center;
            color: #777;
            font-size: 9pt;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td class="head-logo">
            <img src="{{ $logoPath }}" alt="">
        </td>
        <td class="head-brand">
            <div class="brand-kicker">Creative Abilities Therapy Services</div>
            Empowering Every <span class="brand-accent">Child,</span><br>
            Embracing Every <span class="brand-accent">Ability</span>
        </td>
        <td class="head-title">
            HOUR TRACKING
            <div class="head-meta">
                {{ $therapistName }}<br>
                {{ $from->format('Y-M-d') }} to {{ $to->format('Y-M-d') }}
            </div>
        </td>
    </tr>
</table>

@if (empty($sections))
    <div class="empty">No contracts fall inside this date range.</div>
@else
    <p class="legend">
        T. Hours is the funding type &middot; N. Hours is the number authorized
        &middot; R. Hours is what remains
    </p>

    <table class="sheet">
        <thead>
        <tr>
            <th class="left col-client">Client's Name</th>
            @if ($showTherapist)
                <th class="left col-therapist">Therapist</th>
            @endif
            <th class="col-date">Contract Start Date</th>
            <th class="col-date">Contract End Date</th>
            <th class="col-code">T. Hours</th>
            <th class="col-hours">N. Hours</th>
            @foreach ($months as $month)
                <th>{{ $month['label'] }}@if ($spansYears)<br><span style="font-weight: normal;">{{ $month['year'] }}</span>@endif</th>
            @endforeach
            <th class="col-hours">R. Hours</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($sections as $section)
            <tr class="section-row">
                <td colspan="{{ $leadColumns }}">{{ $section['service'] }}</td>
                <td>{{ $hours($section['allotted_hours']) }}</td>
                @foreach ($months as $month)
                    <td></td>
                @endforeach
                <td class="{{ $section['remaining_hours'] < 0 ? 'over' : '' }}">{{ $hours($section['remaining_hours']) }}</td>
            </tr>

            @foreach ($section['rows'] as $row)
                <tr>
                    <td class="left">{{ $row['client_name'] }}</td>
                    @if ($showTherapist)
                        <td class="left">{{ $row['therapist_name'] }}</td>
                    @endif
                    <td>{{ $sheetDate($row['period_start']) }}</td>
                    <td>{{ $sheetDate($row['period_end']) }}</td>
                    <td>{{ $row['funding_code'] ?? '-' }}</td>
                    <td>{{ $hours($row['allotted_hours']) }}</td>
                    @foreach ($months as $month)
                        @php $cell = $row['months'][$month['key']] ?? null; @endphp
                        <td class="{{ $cell === null ? 'muted' : '' }}">{{ $cell === null ? 'x' : $hours($cell) }}</td>
                    @endforeach
                    <td class="{{ $row['remaining_hours'] < 0 ? 'over' : '' }}">{{ $hours($row['remaining_hours']) }}</td>
                </tr>
            @endforeach
        @endforeach

        <tr class="total-row">
            <td class="left" colspan="{{ $leadColumns }}">All services</td>
            <td>{{ $hours($totals['allotted_hours']) }}</td>
            @foreach ($months as $month)
                <td></td>
            @endforeach
            <td class="{{ $totals['remaining_hours'] < 0 ? 'over' : '' }}">{{ $hours($totals['remaining_hours']) }}</td>
        </tr>
        </tbody>
    </table>
@endif

</body>
</html>
