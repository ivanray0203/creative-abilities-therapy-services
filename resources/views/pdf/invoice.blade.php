@php
    /** @var \App\Models\Invoice $invoice */
    $letterhead = config('cats.invoice');
    $lines = $invoice->services ?? [];
    // The reference form keeps a fixed-height grid, so short invoices are
    // padded out with empty rows rather than letting the totals float up.
    $fillerRows = max(0, 22 - count($lines));
    $quantityTotal = collect($lines)->sum(fn ($line) => (float) ($line['numberOfSessions'] ?? 0));
    $money = fn ($value) => number_format((float) $value, 2);
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

        .company { font-size: 9.5pt; line-height: 1.55; }
        .company .bp,
        .company .legal { font-family: Helvetica-Bold, sans-serif; }
        .company .site { text-decoration: underline; }

        .summary { width: 100%; }
        .summary td {
            border: 1px solid #1a1a1a;
            padding: 3px 7px;
            font-size: 9.5pt;
        }
        .summary .label {
            background: #D87E45;
            font-family: Helvetica-Bold, sans-serif;
            width: 46%;
        }
        .summary .value { text-align: center; font-family: Helvetica-Bold, sans-serif; }

        .parties { margin-top: 14px; font-size: 9.5pt; line-height: 1.55; }
        .parties .heading { font-family: Helvetica-Bold, sans-serif; }

        .items {
            margin-top: 16px;
            table-layout: fixed;
            border: 1px solid #1a1a1a;
        }
        .items th {
            background: #D87E45;
            border: 1px solid #1a1a1a;
            font-family: Helvetica-Bold, sans-serif;
            font-size: 9.5pt;
            padding: 4px 3px;
        }
        .items td {
            border-left: 1px dotted #4D4D4D;
            border-right: 1px dotted #4D4D4D;
            border-bottom: 1px dotted #b4b4b4;
            padding: 3px;
            font-size: 9pt;
            height: 15px;
        }
        .items .col-date { width: 13%; text-align: center; }
        .items .col-service { width: 40%; text-align: center; }
        .items .col-rate { width: 14%; text-align: center; }
        .items .col-qty { width: 15%; text-align: center; }
        .items .col-amount { width: 18%; text-align: center; }
        .items tr.last-line td { border-bottom: 1px solid #1a1a1a; }

        .totals td {
            border: 1px solid #1a1a1a;
            padding: 4px 3px;
            font-family: Helvetica-Bold, sans-serif;
            font-size: 9.5pt;
            text-align: center;
        }
        .totals .tagline {
            border: none;
            font-family: Helvetica-Oblique, sans-serif;
            text-align: center;
        }
        .totals .total-label { background: #D87E45; }

        .signatures { margin-top: 26px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; }
        .sig-heading {
            font-family: Helvetica-Bold, sans-serif;
            font-size: 9.5pt;
        }
        .sig-slot { height: 62px; }
        .sig-slot img { max-height: 58px; }
        .sig-rule {
            border-top: 1px solid #1a1a1a;
            width: 62%;
            margin: 0 auto;
        }
        .sig-box {
            border: 1px solid #1a1a1a;
            height: 62px;
            width: 72%;
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
        <td class="head-title">INVOICE</td>
    </tr>
</table>

<table style="margin-top: 10px;">
    <tr>
        <td style="width: 58%; vertical-align: top;">
            <div class="company">
                <div class="bp">Business Partner # {{ $letterhead['business_partner_number'] }}</div>
                <div class="legal">{{ $letterhead['legal_name'] }}</div>
                <div>{{ $letterhead['street'] }}</div>
                <div>{{ $letterhead['city_line'] }}</div>
                <div>{{ $letterhead['phone'] }}</div>
                <div class="site">{{ $letterhead['website'] }}</div>
            </div>
        </td>
        <td style="width: 42%; vertical-align: top;">
            <table class="summary">
                <tr>
                    <td class="label">Invoice #:</td>
                    <td class="value">{{ $invoice->invoice_id ?? $invoice->id }}</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td class="value">{{ $invoice->invoice_date?->format('Y-M-d') ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Amount Due:</td>
                    <td class="value">CA${{ $money($invoice->amount_due) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td style="width: 58%; vertical-align: top;">
            <div class="heading">BILL TO:</div>
            <div>{{ $billTo['name'] }}</div>
            @foreach ($billTo['address'] as $addressLine)
                <div>{{ $addressLine }}</div>
            @endforeach
        </td>
        <td style="width: 42%; vertical-align: top;">
            <div class="heading">CLIENT:</div>
            <div>{{ $clientDetails['name'] }}</div>
            <div>{{ $clientDetails['date_of_birth'] }}</div>
            <div>{{ $clientDetails['number'] }}</div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th class="col-date">Date</th>
            <th class="col-service">Service Provided</th>
            <th class="col-rate">Rate</th>
            <th class="col-qty">Quantity</th>
            <th class="col-amount">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            @php
                $quantity = (float) ($line['numberOfSessions'] ?? 0);
                $rate = (float) ($line['rate_numeric'] ?? 0);
                // Per-line dates are honoured when present; invoices raised
                // from the current form carry one date for the whole document.
                $lineDate = $line['date'] ?? $invoice->invoice_date?->format('Y-M-d');
            @endphp
            <tr>
                <td class="col-date">{{ $lineDate }}</td>
                <td class="col-service">{{ $line['name'] ?? '' }}</td>
                <td class="col-rate">{{ $money($rate) }}</td>
                <td class="col-qty">{{ $money($quantity) }}</td>
                <td class="col-amount">{{ $money($rate * $quantity) }}</td>
            </tr>
        @endforeach

        @for ($i = 0; $i < $fillerRows; $i++)
            <tr class="{{ $i === $fillerRows - 1 ? 'last-line' : '' }}">
                <td class="col-date">&nbsp;</td>
                <td class="col-service">&nbsp;</td>
                <td class="col-rate">&nbsp;</td>
                <td class="col-qty">&nbsp;</td>
                <td class="col-amount">&nbsp;</td>
            </tr>
        @endfor
    </tbody>
</table>

<table class="items totals">
    <tr>
        <td class="tagline" style="width: 53%;">{{ $letterhead['tagline'] }}</td>
        <td class="total-label" style="width: 14%;">TOTAL</td>
        <td style="width: 15%;">{{ $money($quantityTotal) }}</td>
        <td style="width: 18%;">CA${{ $money($invoice->total) }}</td>
    </tr>
</table>

<table class="signatures">
    <tr>
        <td>
            <div class="sig-heading">CLINICAL DIRECTOR:</div>
            <div class="sig-slot">
                @if ($directorSignature)
                    <img src="{{ $directorSignature }}" alt="">
                @endif
            </div>
            <div class="sig-rule"></div>
            <div class="sig-name">{{ $letterhead['clinical_director'] }}</div>
        </td>
        <td>
            <div class="sig-heading">PARENT&#39;S SIGNATURE:</div>
            <div class="sig-box">
                @if ($parentSignature)
                    <img src="{{ $parentSignature }}" alt="" style="max-height: 58px;">
                @else
                    <div class="sig-pending">Awaiting signature</div>
                @endif
            </div>
            <div class="sig-name">{{ $billTo['name'] }}</div>
        </td>
    </tr>
</table>

</body>
</html>
