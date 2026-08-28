/**
 * Whether a team-member position is an aide's.
 *
 * An aide does not bill line-by-line: their record of work is the FSCD time
 * sheet, so the sidebar gives them Hours and Timesheets where a therapist
 * gets Billing and Invoices.
 *
 * Matched on the title's suffix, mirroring `TeamMember::isAide()`
 * (app/Models/TeamMember.php) — the clinic runs both "Behavioural &
 * Developmental Aide" and "Behaviour Aide". The server is the authority:
 * the `aide` route middleware enforces the same split, so a mismatch here
 * only ever costs a redirect, never access.
 */
export function isAidePosition(position: string | null | undefined): boolean {
    return (position ?? '').trim().toLowerCase().endsWith('aide');
}
