<?php

namespace App\Services;

use App\Models\Client;
use App\Models\InvoiceService;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The client and rate-card options behind the invoice and bill forms. Both
 * forms pick from the same two lists, so the rules for what is offered —
 * and at what price — live here rather than in either controller.
 */
class BillingFormOptions
{
    /**
     * Session statuses that mean the visit has actually been delivered, and
     * so can be billed for.
     *
     * @var array<int, string>
     */
    public const DELIVERED_SESSION_STATUSES = ['pending', 'confirmed', 'completed'];

    /**
     * The invoice rate card, priced for whoever is raising the bill.
     *
     * The clinic bills the family at its published rates. A therapist
     * billing the clinic bills at their own, which falls back to the
     * published rate wherever they have no override.
     *
     * @return Collection<int, array{id: int, name: string, code: string, discipline: string, rate_fscd: ?string, rate_private: ?string}>
     */
    public function services(User $user): Collection
    {
        $services = InvoiceService::query()->active()->orderBy('sort_order')->get();

        $teamMember = $user->isAdmin()
            ? null
            : TeamMember::query()->where('user_id', $user->id)->with('invoiceServiceRates')->first();

        return $services->map(fn (InvoiceService $service): array => [
            'id' => $service->id,
            'name' => $service->name,
            'code' => $service->code,
            'discipline' => $service->discipline,
            'rate_fscd' => $teamMember?->rateFor($service, 'fscd') ?? $service->rate_fscd,
            'rate_private' => $teamMember?->rateFor($service, 'private') ?? $service->rate_private,
        ]);
    }

    /**
     * Clients billable from the form: those with at least one session
     * already delivered. A visit counts as delivered once the therapist has
     * clocked out of it — `pending` (awaiting the client's sign-off),
     * `confirmed` (signed off) and `completed` (paid for). Anything merely
     * booked, cancelled or missed has nothing to bill for yet.
     *
     * A therapist only sees clients they themselves have delivered a session
     * to; admins bill for anyone. The record being edited keeps its own
     * client selectable so reopening it never renders an empty field.
     *
     * @return Collection<int, Client>
     */
    public function clients(User $user, ?int $keepClientId = null): Collection
    {
        return Client::query()
            ->where(function (Builder $selectable) use ($user, $keepClientId): void {
                $selectable->whereHas('sessions', fn (Builder $sessions) => $sessions
                    ->whereIn('status', self::DELIVERED_SESSION_STATUSES)
                    ->when(! $user->isAdmin(), fn (Builder $own) => $own->where('therapist_id', $user->id)));

                if ($keepClientId !== null) {
                    $selectable->orWhere('clients.id', $keepClientId);
                }
            })
            ->with('originalIntake:id,child_first_name,child_last_name,funding_source')
            ->get(['id', 'original_intake_id']);
    }
}
