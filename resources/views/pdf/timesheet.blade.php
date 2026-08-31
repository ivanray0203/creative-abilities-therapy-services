@php
    /** @var \App\Models\Timesheet $timesheet */
    $letterhead = config('cats.invoice');
    $rows = $timesheet->rows ?? [];
    // The reference form keeps a fixed-height grid, so a short period is
    // padded out with empty rows rather than letting the totals float up.
    $fillerRows = max(0, 26 - count($rows));
    $hours = fn ($value) => number_format((float) $value, 2);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 30px; }

        body {
            font-family: Helvetica, sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
        }

        table { border-collapse: collapse; width: 100%; }

        .head-logo { width: 84px; vertical-align: top; }
        .head-logo img { width: 80px; }

        .head-brand {
            vertical-align: top;
            padding-top: 6px;
            font-size: 13.5pt;
            font-family: Helvetica-Bold, sans-serif;
            line-height: 1.25;
        }
        .head-brand .brand-kicker {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 7.5pt;
            color: #D87E45;
        }
        .brand-accent { color: #D87E45; }

        .head-title {
            text-align: right;
            vertical-align: top;
            font-family: Helvetica-Bold, sans-serif;
            font-size: 34pt;
            color: #4D4D4D;
        }

        .company { font-size: 9.5pt; line-height: 1.55; text-align: right; }
        .company .bp,
        .company .legal { font-family: Helvetica-Bold, sans-serif; }
        .company .site { text-decoration: underline; }

        .client-block { font-size: 9.5pt; line-height: 1.75; }
        .client-block .label {
            font-family: Helvetica-Bold, sans-serif;
            width: 110px;
        }
        .client-block .value { font-family: Helvetica-Bold, sans-serif; }

        .grid {
            margin-top: 10px;
            table-layout: fixed;
            border: 1px solid #1a1a1a;
        }
        .grid th {
            background: #D87E45;
            border: 1px solid #1a1a1a;
            font-family: Helvetica-Bold, sans-serif;
            font-size: 9.5pt;
            padding: 4px 3px;
            text-align: center;
        }
        .grid th.plain {
            background: #ffffff;
            font-size: 11pt;
        }
        .grid td {
            border-left: 1px solid #1a1a1a;
            border-right: 1px solid #1a1a1a;
            border-bottom: 1px dotted #b4b4b4;
            padding: 3px;
            font-size: 9pt;
            height: 15px;
            text-align: center;
        }
        .grid td.col-date { border-right: 1px solid #1a1a1a; }
        .grid tr.last-line td { border-bottom: 1px solid #1a1a1a; }

        .col-date { width: 20%; }
        .col-hours { width: 20%; }

        .totals { table-layout: fixed; }
        .totals td {
            border: 1px solid #1a1a1a;
            padding: 4px 3px;
            font-family: Helvetica-Bold, sans-serif;
            font-size: 9.5pt;
            text-align: center;
        }
        .totals .total-label { background: #D87E45; }
        .totals .blank { border: none; }

        .signatures { margin-top: 26px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; }
        .sig-heading {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 9.5pt;
        }
        .sig-box {
            border: 1px dotted #4D4D4D;
            height: 62px;
            width: 78%;
            margin: 0 auto;
        }
        .sig-box img { max-height: 58px; }
        .sig-rule {
            border-top: 1px solid #1a1a1a;
            width: 78%;
            margin: 0 auto;
        }
        .sig-name { padding-top: 4px; font-size: 9.5pt; }
        .sig-pending { color: #8a8a8a; font-size: 8.5pt; padding-top: 22px; }
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
        <td class="head-title">TIME SHEET</td>
    </tr>
</table>

<table style="margin-top: 6px;">
    <tr>
        <td style="width: 50%; vertical-align: bottom;">
            <table class="client-block">
                <tr>
                    <td class="label">CLIENT:</td>
                    <td class="value">{{ $clientDetails['name'] }}</td>
                </tr>
                <tr>
                    <td class="label">DOB:</td>
                    <td class="value">{{ $clientDetails['date_of_birth'] }}</td>
                </tr>
                <tr>
                    <td class="label">FSCD FILE #:</td>
                    <td class="value">{{ $clientDetails['fscd_file_number'] }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <div class="company">
                <div class="bp">Business Partner # {{ $letterhead['business_partner_number'] }}</div>
                <div class="legal">{{ $letterhead['legal_name'] }}</div>
                <div>{{ $letterhead['street'] }}</div>
                <div>{{ $letterhead['city_line'] }}</div>
                <div>{{ $letterhead['phone'] }}</div>
                <div class="site">{{ $letterhead['website'] }}</div>
            </div>
        </td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th class="plain col-date" rowspan="3">Date</th>
            <th colspan="4">Types of Service and Hours</th>
        </tr>
        <tr>
            <th class="col-hours" rowspan="2">Hourly Respite</th>
            <th class="col-hours" rowspan="2">Community Support Aide</th>
            <th colspan="2">Behavioural or Developmental Aide Support</th>
        </tr>
        <tr>
            <th class="col-hours">Direct Hours</th>
            <th class="col-hours">Indirect Hours</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <td class="col-date">{{ $row['date'] ?? '' }}</td>
                <td>{{ $hours($row['hourly_respite'] ?? 0) }}</td>
                <td>{{ $hours($row['community_support'] ?? 0) }}</td>
                <td>{{ $hours($row['bda_direct'] ?? 0) }}</td>
                <td>{{ $hours($row['bda_indirect'] ?? 0) }}</td>
            </tr>
        @endforeach

        @for ($i = 0; $i < $fillerRows; $i++)
            <tr class="{{ $i === $fillerRows - 1 ? 'last-line' : '' }}">
                <td class="col-date">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="total-label col-date">TOTAL HOURS</td>
        <td class="col-hours">{{ $hours($timesheet->total_hourly_respite) }}</td>
        <td class="col-hours">{{ $hours($timesheet->total_community_support) }}</td>
        <td class="col-hours">{{ $hours($timesheet->total_bda_direct) }}</td>
        <td class="col-hours">{{ $hours($timesheet->total_bda_indirect) }}</td>
    </tr>
    <tr>
        <td class="blank col-date">&nbsp;</td>
        <td class="blank col-hours">&nbsp;</td>
        <td class="total-label col-hours">TOTAL HOURS</td>
        <td colspan="2">{{ $hours($timesheet->total_hours) }}</td>
    </tr>
</table>

<table class="signatures">
    <tr>
        <td>
            <div class="sig-heading">AIDE&#39;S SIGNATURE:</div>
            <div class="sig-box">
                @if ($aideSignature)
                    <img src="{{ $aideSignature }}" alt="">
                @endif
            </div>
            <div class="sig-rule"></div>
            <div class="sig-name">{{ $aideName }}</div>
        </td>
        <td>
            <div class="sig-heading">PARENT&#39;S SIGNATURE:</div>
            <div class="sig-box">
                @if ($parentSignature)
                    <img src="{{ $parentSignature }}" alt="">
                @else
                    <div class="sig-pending">Awaiting signature</div>
                @endif
            </div>
            <div class="sig-rule"></div>
            <div class="sig-name">{{ $parentName }}</div>
        </td>
    </tr>
</table>

</body>
</html>
