@component('mail::message')
# Invoice {{ $invoice->invoice_id }}

@component('mail::panel')
**Amount Due:** ${{ number_format((float) $invoice->total, 2) }}<br>
**Due Date:** {{ $invoice->due_date }}
@endcomponent

@component('mail::button', ['url' => url("/client/invoices/{$invoice->id}")])
View Invoice
@endcomponent

Thanks,<br>
{{ config('cats.name') }}
@endcomponent
