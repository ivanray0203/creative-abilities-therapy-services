<?php

namespace App\Http\Requests;

/**
 * Same field set as StoreInvoiceRequest — reused for reschedule/edit of
 * line items, dates, and draft/send status.
 */
class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    //
}
