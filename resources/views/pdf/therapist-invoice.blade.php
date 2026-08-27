@php
    /** @var \App\Models\Invoice $invoice */
    // The therapist's invoice to the clinic, in the format their statement
    // already uses on screen (resources/js/components/invoices/
    // monthly-invoice-printable.tsx): one row per piece of work, naming the
    // child it was for, since a statement spans every client they saw.
    //
    // Deliberately not pdf/invoice.blade.php: that is the clinic's document
    // to a family, with the client block and signature boxes a bill to the
    // clinic has no use for.
    $letterhead = config('cats.invoice');
    $lines = $invoice->services ?? [];
    $money = fn ($value) => number_format((float) $value, 2);
    $sheetDate = function (?string $date) {
        if (blank($date)) {
            return '-';
        }

        return \Illuminate\Support\Carbon::parse($date)->format('Y-M-d');
    };
    $billToLines = array_values(array_filter(
        preg_split('/\r\n|\r|\n/', (string) $invoice->bill_to_address) ?: [],
        fn (string $line): bool => trim($line) !== '',
    ));
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

        .summary { width: 100%; }
        .summary td {
            border: 1px solid #1a1a1a;
            padding: 3px 7px;
            font-size: 9.5pt;
        }
        .summary .label { font-family: Helvetica-Bold, sans-serif; width: 52%; }
        .summary .value { text-align: center; font-family: Helvetica-Bold, sans-serif; }
        .summary .due { background: #F5DFD1; }

        .parties { margin-top: 14px; font-size: 9.5pt; line-height: 1.5; }
        .parties .heading { font-family: Helvetica-Bold, sans-serif; }
        .parties .from { padding-top: 8px; color: #4D4D4D; }

        .items { margin-top: 14px; }
        .items th,
        .items td {
            border: 1px solid #1a1a1a;
            padding: 4px 6px;
            font-size: 9pt;
        }
        .items th {
            background: #F1F1F1;
            font-family: Helvetica-Bold, sans-serif;
            text-align: center;
        }
        .items .num { text-align: right; }
        .items .empty { text-align: center; color: #8a8a8a; padding: 14px 6px; }

        .totals td {
            border: 1px solid #1a1a1a;
            padding: 4px 6px;
            font-family: Helvetica-Bold, sans-serif;
        }
        .totals .label { text-align: right; }
        .totals .amount { text-align: right; background: #F5DFD1; width: 18%; }

        .period { margin-top: 10px; font-size: 8.5pt; color: #4D4D4D; }
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
            <div class="parties">
                <div class="heading">BILL TO:</div>
                <div>{{ $invoice->bill_to_name ?? $letterhead['legal_name'] }}</div>
                @foreach ($billToLines as $addressLine)
                    <div>{{ $addressLine }}</div>
                @endforeach
                <div class="from">
                    From {{ $therapist['name'] }}@if ($therapist['email']) &middot; {{ $therapist['email'] }}@endif
                </div>
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
                    <td class="value due">CA${{ $money($invoice->amount_due) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width: 14%;">Date</th>
            <th style="width: 24%;">Client</th>
            <th style="width: 30%;">Service Provided</th>
            <th style="width: 11%;">Rate</th>
            <th style="width: 10%;">Quantity</th>
            <th style="width: 11%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            @php
                $quantity = (float) ($line['numberOfSessions'] ?? 0);
                $rate = (float) ($line['rate_numeric'] ?? 0);
            @endphp
            <tr>
                <td>{{ $sheetDate($line['date'] ?? $invoice->invoice_date?->toDateString()) }}</td>
                <td>{{ $line['client'] ?? '-' }}</td>
                <td>{{ $line['name'] ?? '' }}</td>
                <td class="num">{{ $money($rate) }}</td>
                <td class="num">{{ $money($quantity) }}</td>
                <td class="num">{{ $money($rate * $quantity) }}</td>
            </tr>
        @endforeach

        @if (count($lines) === 0)
            <tr>
                <td class="empty" colspan="6">No work billed for this period.</td>
            </tr>
        @endif
    </tbody>
</table>

<table class="items totals">
    <tr>
        <td class="label" style="width: 82%;">Total</td>
        <td class="amount">CA${{ $money($invoice->total) }}</td>
    </tr>
</table>

@if ($invoice->period_start && $invoice->period_end)
    <div class="period">
        Covering {{ $invoice->period_start->format('Y-M-d') }} to {{ $invoice->period_end->format('Y-M-d') }}.
    </div>
@endif

</body>
</html>
