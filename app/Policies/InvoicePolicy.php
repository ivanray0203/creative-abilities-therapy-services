<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Services\ClientContext;

/**
 * Phase 18 — before this, `destroy` and `markPaid` had no authorization at
 * all; they were reachable only because no non-admin route pointed at them.
 * Encoding the rules here means adding such a route can't silently expose
 * them.
 */
class InvoicePolicy
{
    public function __construct(private ClientContext $clientContext) {}

    /**
     * Billing runs in two hops: a therapist invoices the clinic for the work,
     * and the clinic invoices the parent. Each side only ever sees the bill
     * it is party to — a therapist's invoice is between them and the admins,
     * so the family never sees it even though it names their child.
     *
     * A parent may open any of their children's invoices, not just the one
     * the portal switcher currently has selected.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isAdmin()) {
            // A therapist's per-client bill is their own working record until
            // the month closes; the admin sees the monthly statement instead.
            return $invoice->billed_by !== 'therapist' || $invoice->is_monthly;
        }

        if ($user->isTherapist()) {
            return $invoice->billed_by === 'therapist' && $invoice->therapist_id === $user->id;
        }

        return $user->isClient()
            && $invoice->billed_by === 'admin'
            && $this->clientContext->owns($user, $invoice->client_id);
    }

    /**
     * Billing is staff-only — clients never edit or resend their own invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // A bill already rolled into a monthly statement is part of what the
        // clinic has been billed, so it is closed to further edits.
        if ($invoice->monthly_invoice_id !== null) {
            return false;
        }

        // The statement itself is generated from its bills, never hand-edited.
        if ($invoice->is_monthly) {
            return false;
        }

        return ($user->isAdmin() && $invoice->billed_by === 'admin')
            || ($invoice->billed_by === 'therapist' && $invoice->therapist_id === $user->id);
    }

    /**
     * Only the parent the invoice is addressed to may sign it, and only once
     * — a signed invoice is a record of what they agreed to, so it is not
     * re-signable.
     */
    public function sign(User $user, Invoice $invoice): bool
    {
        return $user->isClient()
            && $invoice->billed_by === 'admin'
            && $invoice->signed_invoice === null
            && $this->clientContext->owns($user, $invoice->client_id);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin();
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin();
    }
}
