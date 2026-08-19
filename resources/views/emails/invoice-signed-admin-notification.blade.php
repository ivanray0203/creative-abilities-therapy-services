@component('mail::message')
# Signed Invoice Returned

A parent has signed and returned an invoice.

@component('mail::table')
| | |
|:--|:--|
| **Invoice** | {{ $invoice->invoice_id }} |
| **Client** | {{ optional($invoice->client)->displayName() ?? 'Unknown client' }} |
| **Amount due** | CA${{ number_format((float) $invoice->amount_due, 2) }} |
| **Signed** | {{ now()->format('j F Y') }} |
@endcomponent

The signed copy has been filed on Drive under `Invoice/signed`.

@component('mail::button', ['url' => url("/admin/invoices/{$invoice->id}")])
Open Invoice
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
