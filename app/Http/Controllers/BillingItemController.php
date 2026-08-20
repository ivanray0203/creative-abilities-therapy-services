<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillingItemRequest;
use App\Models\BillingItem;
use App\Models\Client;
use App\Models\ScheduleSession;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BillingFormOptions;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Billing, as the therapist sees it: one row per service delivered, raised
 * as it happens. Nothing is invoiced here — the lines sit unbilled until
 * the month closes and the generated invoice claims them, at which point
 * `invoice_id` is filled in and the line is frozen.
 *
 * Scoped by the acting user's role rather than by which route group was
 * used, matching InvoiceController and SessionController.
 */
class BillingItemController extends Controller
{
    public function __construct(private BillingFormOptions $formOptions) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = $this->scopedQuery($user);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $items = (clone $baseQuery)
            ->when($status === 'billed', fn (Builder $query) => $query->whereNotNull('invoice_id'))
            ->when($status === 'unbilled', fn (Builder $query) => $query->whereNull('invoice_id'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('billing_number', 'like', "%{$search}%")
                        ->orWhere('service_name', 'like', "%{$search}%")
                        ->orWhereHas('client.originalIntake', function (Builder $child) use ($search): void {
                            $child->where('child_first_name', 'like', "%{$search}%")
                                ->orWhere('child_last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->with(['client.originalIntake', 'therapist', 'invoice:id,invoice_id'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('billing/index', [
            'items' => $items,
            'stats' => [
                'unbilled_total' => (clone $baseQuery)->whereNull('invoice_id')->sum('amount'),
                'unbilled_count' => (clone $baseQuery)->whereNull('invoice_id')->count(),
                'billed_total' => (clone $baseQuery)->whereNotNull('invoice_id')->sum('amount'),
                'this_month_total' => (clone $baseQuery)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
            ],
            'filters' => ['search' => $search, 'status' => $status],
            'role' => $user->role,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('billing/create', [
            'role' => $user->role,
            'clients' => $this->formOptions->clients($user),
            'services' => $this->formOptions->services($user),
            // A bill belongs to the therapist who delivered the work. Only an
            // admin raises one on someone else's behalf, so only they choose.
            'therapists' => $user->isAdmin() ? $this->therapists() : [],
        ]);
    }

    /**
     * Saves one row per service on the form — a bill is a set of lines, not
     * a document, so nothing is totalled or sent here.
     */
    public function store(StoreBillingItemRequest $request, ReferenceNumberGenerator $referenceNumbers): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $client = Client::query()->findOrFail((int) $validated['client_id']);

        $this->assertSessionBelongsToClient($validated['session_id'] ?? null, $client);

        // The bill is credited to whoever delivered the work: the therapist
        // raising it, or the one an admin raises it for.
        $therapistId = $user->isAdmin() ? (int) $validated['therapist_id'] : $user->id;

        /** @var array<int, array<string, mixed>> $services */
        $services = $validated['services'];

        DB::transaction(function () use ($services, $validated, $client, $user, $therapistId, $referenceNumbers): void {
            foreach ($services as $line) {
                $quantity = (float) $line['quantity'];
                $rate = (float) $line['rate'];

                BillingItem::create([
                    'billing_number' => $referenceNumbers->billingItem(),
                    'therapist_id' => $therapistId,
                    'client_id' => $client->id,
                    'session_id' => $validated['session_id'] ?? null,
                    'invoice_service_id' => $line['invoice_service_id'] ?? null,
                    'service_name' => $line['name'],
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'amount' => round($quantity * $rate, 2),
                    'issued_by_id' => $user->id,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        });

        $count = count($services);

        AuditLogger::log(
            'Created bill',
            'Billing',
            "Created {$count} billing item(s) for client #{$client->id}",
        );

        return to_route($this->routeName($request, 'billing.index'))
            ->with('success', "{$count} billing item(s) saved.");
    }

    /**
     * A bill can be corrected right up until an invoice claims it; after
     * that the line is what the client was charged, so it stays put.
     */
    public function destroy(Request $request, BillingItem $billingItem): RedirectResponse
    {
        abort_unless($this->canManage($request->user(), $billingItem), 404);

        if ($billingItem->invoice_id !== null) {
            return back()->with('error', 'This item has already been invoiced and can no longer be removed.');
        }

        $billingItem->delete();

        AuditLogger::log('Deleted billing item', 'Billing', "Deleted billing item {$billingItem->billing_number}", 'warning');

        return back()->with('success', 'Billing item removed.');
    }

    /**
     * Whose bills a user sees: their own, and no one else's.
     *
     * A therapist's ledger is the work they billed for themselves — not
     * even a bill an admin raised on their behalf. The admin side is the
     * clinic's own billing: what the admins raised, never a therapist's
     * lines, which reach them as a monthly statement instead.
     *
     * @return Builder<BillingItem>
     */
    private function scopedQuery(User $user): Builder
    {
        if ($user->isAdmin()) {
            return BillingItem::query()
                ->whereHas('issuedBy', fn (Builder $issuer) => $issuer->where('role', 'admin'));
        }

        return BillingItem::query()->where('issued_by_id', $user->id);
    }

    private function routeName(Request $request, string $suffix): string
    {
        return $request->user()->isAdmin() ? "admin.{$suffix}" : "therapist.{$suffix}";
    }

    /**
     * Therapists a bill can be raised for. Admin-only — a therapist bills
     * under their own name.
     *
     * @return Collection<int, User>
     */
    private function therapists(): Collection
    {
        return User::query()
            ->where('role', 'therapist')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    /** A bill is only ever managed by whoever can see it — the user who raised it. */
    private function canManage(User $user, BillingItem $item): bool
    {
        if ($user->isAdmin()) {
            return $item->issuedBy?->isAdmin() === true;
        }

        return $item->issued_by_id === $user->id;
    }

    /**
     * A bill points at the visit it came out of, so the visit has to be the
     * client's own — otherwise one family's session would be billed to
     * another.
     */
    private function assertSessionBelongsToClient(mixed $sessionId, Client $client): void
    {
        if (! $sessionId) {
            return;
        }

        $session = ScheduleSession::query()->find((int) $sessionId);

        if ($session?->client_id !== $client->id) {
            throw ValidationException::withMessages([
                'session_id' => 'That session belongs to a different client.',
            ]);
        }
    }
}
