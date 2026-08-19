<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignInvoiceRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceResendMail;
use App\Mail\InvoiceSignedAdminNotification;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ClientContext;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\InvoiceDocumentService;
use App\Services\PdfService;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared invoicing controller, reachable from the admin, therapist, and
 * client role route groups (reference: cats-backend/cats/views.py
 * InvoiceViewSet and cats-frontend/src/pages/admin/InvoicesPage.tsx). Data
 * is scoped by the acting user's role rather than by which route group was
 * used — mirrors SessionController's shared/role-aware pattern.
 */
class InvoiceController extends Controller
{
    /**
     * Session statuses that mean the visit has actually been delivered, and
     * so can be billed for.
     *
     * @var array<int, string>
     */
    private const DELIVERED_SESSION_STATUSES = ['pending', 'confirmed', 'completed'];

    public function __construct(private ClientContext $clientContext) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = $this->scopedQuery($user);

        $quick = (string) $request->query('quick', 'all');
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        // Which side of the two-hop ledger to show. Only the admin sees both,
        // so the filter is ignored for everyone else.
        $direction = $user->isAdmin() ? (string) $request->query('direction', 'all') : 'all';

        $filtered = (clone $baseQuery)
            ->when($quick === 'paid', fn (Builder $query) => $query->where('status', 'paid'))
            ->when($quick === 'unpaid', fn (Builder $query) => $query->whereIn('status', ['sent', 'unpaid']))
            ->when($quick === 'overdue', fn (Builder $query) => $query->where('status', 'overdue'))
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($direction !== 'all', fn (Builder $query) => $query->where('billed_by', $direction))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('client.originalIntake', function (Builder $inner) use ($search): void {
                    $inner->where('child_first_name', 'like', "%{$search}%")
                        ->orWhere('child_last_name', 'like', "%{$search}%");
                });
            });

        $invoices = (clone $filtered)
            ->with(['client.originalIntake', 'therapist'])
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'paid_this_month' => (clone $baseQuery)
                ->where('status', 'paid')
                ->whereMonth('paid_date', now()->month)
                ->whereYear('paid_date', now()->year)
                ->sum('total'),
            'pending' => (clone $baseQuery)->whereIn('status', ['sent', 'unpaid', 'draft'])->count(),
            'overdue' => (clone $baseQuery)->where('status', 'overdue')->count(),
            'total_revenue' => (clone $baseQuery)->where('status', 'paid')->sum('total'),
            // What the clinic still owes its therapists. Only the admin sits
            // on that side of the ledger, so nobody else is shown a figure.
            'owed_to_therapists' => $user->isAdmin()
                ? Invoice::query()->where('billed_by', 'therapist')->whereNot('status', 'paid')->sum('total')
                : null,
        ];

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => ['quick' => $quick, 'search' => $search, 'status' => $status, 'direction' => $direction],
            'role' => $user->role,
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->assertCanView($invoice, $request->user());

        $invoice->load([
            'client.originalIntake', 'therapist', 'session',
            'linkedTherapistInvoice.therapist', 'linkedAdminInvoices.client.originalIntake',
        ]);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'role' => $request->user()->role,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('invoices/create', [
            'role' => $request->user()->role,
            'clients' => $this->clientOptions($request->user()),
            'services' => $this->serviceOptions(),
            'therapistInvoices' => $this->recoverableTherapistInvoices($request->user()),
        ]);
    }

    public function store(StoreInvoiceRequest $request, ReferenceNumberGenerator $referenceNumberGenerator): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $billedBy = $user->isAdmin() ? 'admin' : 'therapist';
        $client = Client::query()->findOrFail((int) $validated['client_id']);

        $invoice = new Invoice([
            ...$this->invoiceAttributes($validated),
            'client_id' => $client->id,
            'billing_account_id' => $client->billing?->id,
            'therapist_id' => $user->isAdmin() ? null : $user->id,
            'billed_by' => $billedBy,
            'tax_percentage' => 0,
            'invoice_id' => $referenceNumberGenerator->invoice(),
            'status' => $validated['action'] === 'send' ? 'sent' : 'draft',
            'issued_by_id' => $user->id,
            'timeline' => [$this->timelineEntry('Invoice created')],
        ]);

        $invoice->calculateTotals();
        $invoice->linked_therapist_invoice_id = $this->recoveredTherapistInvoiceId($validated, $billedBy);
        $invoice->save();

        if ($invoice->billed_by === 'admin') {
            app(InvoiceDocumentService::class)->storeUnsigned($invoice);
        }

        if ($invoice->status === 'sent') {
            $this->emailInvoice($invoice);
        }

        AuditLogger::log('Created invoice', 'Invoices', "Created invoice {$invoice->invoice_id}");

        return to_route($this->routeName($request, 'invoices.show'), $invoice)->with('success', 'Invoice created successfully.');
    }

    /**
     * The invoice document itself, for a client (clinic → parent) invoice:
     * the copy the parent signed when there is one, otherwise the PDF filed
     * when the invoice was raised.
     */
    public function pdf(Request $request, Invoice $invoice, PdfService $pdfService): HttpResponse
    {
        $this->assertCanView($invoice, $request->user());
        // Therapist bills have no stored document and are not parent-facing.
        abort_unless($invoice->billed_by === 'admin', 404);

        $stored = $invoice->signed_invoice ?? $invoice->not_signed_invoice;
        $filename = 'invoice-'.($invoice->invoice_id ?? $invoice->id).'.pdf';

        $contents = filled($stored) ? $this->storedInvoiceContents($stored) : null;

        // Nothing on file (or it could not be read) — render the document from
        // the invoice as it stands rather than showing the viewer an error.
        $contents ??= $pdfService->invoice($invoice);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            // The modal frames this on our own origin.
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * The bytes behind a stored invoice URL.
     *
     * Drive's own links either force a download (`webContentLink`) or refuse
     * to be framed (`webViewLink`), so the file is fetched and served under
     * this app's origin instead of redirected to. The file id is recovered
     * from the URL, which keeps invoices filed earlier readable.
     */
    private function storedInvoiceContents(string $url): ?string
    {
        $localBase = rtrim(Storage::disk('public')->url(''), '/');

        if (str_starts_with($url, $localBase)) {
            $path = ltrim(substr($url, strlen($localBase)), '/');

            return Storage::disk('public')->exists($path)
                ? Storage::disk('public')->get($path)
                : null;
        }

        if (! preg_match('~(?:[?&]id=|/d/)([A-Za-z0-9_-]{10,})~', $url, $matches)) {
            return null;
        }

        return app(DriveStorage::class)->get($matches[1]);
    }

    /**
     * The parent returns the invoice with their signature drawn into the
     * signature box. The signed copy is filed separately — the unsigned
     * original stays exactly as it was issued.
     */
    public function sign(SignInvoiceRequest $request, Invoice $invoice, InvoiceDocumentService $documents): RedirectResponse
    {
        abort_unless($request->user()->can('sign', $invoice), 404);

        $stored = $documents->storeSigned($invoice, $request->validated()['signature']);

        if ($stored === null) {
            return back()->with('error', 'We could not file your signed invoice. Please try again.');
        }

        $invoice->forceFill([
            'timeline' => [
                ...($invoice->timeline ?? []),
                $this->timelineEntry('Invoice signed by parent'),
            ],
        ])->save();

        AuditLogger::log(
            'Invoice signed',
            'Invoices',
            "Invoice {$invoice->invoice_id} signed by the parent",
            'success',
            $request->user()->email,
        );

        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails)->send(new InvoiceSignedAdminNotification($invoice));
        }

        return back()->with('success', 'Thank you — your signed invoice has been sent.');
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->assertOwnsOrAdmin($invoice, $request->user());

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'role' => $request->user()->role,
            'clients' => $this->clientOptions($request->user(), $invoice),
            'services' => $this->serviceOptions(),
            'therapistInvoices' => $this->recoverableTherapistInvoices($request->user(), $invoice),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->assertOwnsOrAdmin($invoice, $request->user());

        $validated = $request->validated();

        $invoice->fill($this->invoiceAttributes($validated));

        if ($invoice->status !== 'paid') {
            $invoice->status = $validated['action'] === 'send' ? 'sent' : 'draft';
        }

        $invoice->calculateTotals();
        $invoice->linked_therapist_invoice_id = $this->recoveredTherapistInvoiceId(
            $validated,
            (string) $invoice->billed_by,
        );
        $invoice->timeline = [
            ...($invoice->timeline ?? []),
            $this->timelineEntry('Invoice updated'),
        ];
        $invoice->save();

        if ($validated['action'] === 'send' && $invoice->status === 'sent') {
            $this->emailInvoice($invoice);
        }

        AuditLogger::log('Updated invoice', 'Invoices', "Updated invoice {$invoice->invoice_id}");

        return to_route($this->routeName($request, 'invoices.show'), $invoice)->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $invoice), 404);

        $invoice->delete();

        AuditLogger::log('Deleted invoice', 'Invoices', "Deleted invoice {$invoice->invoice_id}", 'warning');

        return to_route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function generateFromSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:schedule_sessions,id'],
        ]);

        $session = ScheduleSession::query()->with(['client', 'service', 'clientServices.service'])->findOrFail((int) $validated['session_id']);

        if ($session->status !== 'completed') {
            throw ValidationException::withMessages([
                'session_id' => 'Only completed sessions can be invoiced.',
            ]);
        }

        $user = $request->user();
        $billedBy = $user->isAdmin() ? 'admin' : 'therapist';
        $client = $session->client;

        // A visit can deliver several availed services, so the line item is
        // priced at their combined rate.
        $linkedServices = $session->clientServices
            ->map(fn (ClientService $clientService): ?float => $clientService->service?->base_price)
            ->filter();
        $directService = optional($session->service);
        $rate = $linkedServices->isNotEmpty()
            ? (float) $linkedServices->sum()
            : (float) ($directService->base_price ?? 0);
        $serviceName = $directService->name ?? $session->service_name ?? 'Session';

        $invoice = new Invoice([
            'client_id' => $client->id,
            'billing_account_id' => $client->billing?->id,
            'session_id' => $session->id,
            'therapist_id' => $user->isAdmin() ? $session->therapist_id : $user->id,
            'billed_by' => $billedBy,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_percentage' => 0,
            'services' => [$this->serviceLineItem(
                $serviceName,
                1,
                $rate,
            )],
            'status' => 'draft',
            'issued_by_id' => $user->id,
            'timeline' => [$this->timelineEntry('Invoice generated from session')],
        ]);

        $invoice->invoice_id = app(ReferenceNumberGenerator::class)->invoice();
        $invoice->calculateTotals();
        $invoice->linked_therapist_invoice_id = $this->recoveredTherapistInvoiceId(
            ['session_id' => $session->id],
            $billedBy,
        );
        $invoice->save();

        if ($invoice->billed_by === 'admin') {
            app(InvoiceDocumentService::class)->storeUnsigned($invoice);
        }

        AuditLogger::log('Generated invoice from session', 'Invoices', "Generated invoice {$invoice->invoice_id} from session #{$session->id}");

        return to_route($this->routeName($request, 'invoices.show'), $invoice)->with('success', 'Invoice generated from session.');
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->user()->can('markPaid', $invoice), 404);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_date' => now()->toDateString(),
            'timeline' => [
                ...($invoice->timeline ?? []),
                $this->timelineEntry('Invoice marked as paid'),
            ],
        ]);

        if ($invoice->session_id) {
            $session = ScheduleSession::query()->find($invoice->session_id);

            if ($session && ! in_array($session->status, ['completed', 'cancelled'], true)) {
                $session->update(['status' => 'completed']);
            }
        }

        AuditLogger::log('Marked invoice paid', 'Invoices', "Marked invoice {$invoice->invoice_id} as paid");

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function resend(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->assertOwnsOrAdmin($invoice, $request->user());

        $invoice->update([
            'timeline' => [
                ...($invoice->timeline ?? []),
                $this->timelineEntry('Invoice resent'),
            ],
        ]);

        $this->emailInvoice($invoice);

        AuditLogger::log('Resent invoice', 'Invoices', "Resent invoice {$invoice->invoice_id}");

        return back()->with('success', 'Invoice resent.');
    }

    /**
     * Emails the invoice to the client's parent — the linked account's email
     * if one exists, otherwise the primary parent email on the originating
     * intake. Silently does nothing if neither is available.
     */
    /**
     * Money moves in two hops — the client pays the clinic, the clinic pays
     * the therapist — so an invoice goes to whoever owes it. A therapist's
     * bill is addressed to the admins; only the clinic's own bill reaches the
     * parent.
     */
    private function emailInvoice(Invoice $invoice): void
    {
        foreach ($this->invoiceRecipients($invoice) as $recipientEmail) {
            Mail::to($recipientEmail)->send(new InvoiceResendMail($invoice));
        }
    }

    /**
     * @return array<int, string>
     */
    private function invoiceRecipients(Invoice $invoice): array
    {
        if ($invoice->billed_by === 'therapist') {
            return User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->pluck('email')
                ->all();
        }

        $client = optional($invoice->client);
        $recipientEmail = optional($client->user)->email ?? optional($client->originalIntake)->primary_parent_email;

        return $recipientEmail !== null ? [$recipientEmail] : [];
    }

    public function byTherapist(User $user): JsonResponse
    {
        return response()->json(
            Invoice::query()->where('therapist_id', $user->id)->get(),
        );
    }

    public function byClient(Client $client): JsonResponse
    {
        return response()->json(
            Invoice::query()->where('client_id', $client->id)->get(),
        );
    }

    /**
     * @return Builder<Invoice>
     */
    private function scopedQuery(User $user): Builder
    {
        if ($user->isAdmin()) {
            return Invoice::query();
        }

        if ($user->isTherapist()) {
            return Invoice::query()
                ->where('therapist_id', $user->id)
                ->where('billed_by', 'therapist');
        }

        // The list follows the portal switcher — one child at a time.
        return Invoice::query()
            ->where('client_id', $this->clientContext->currentId($user) ?? 0)
            ->where('billed_by', 'admin');
    }

    /*
     * The rules live in InvoicePolicy (Phase 18). These wrappers keep call
     * sites unchanged and keep denials as 404 rather than the 403
     * `authorize()` would raise.
     */

    private function assertCanView(Invoice $invoice, User $user): void
    {
        abort_unless($user->can('view', $invoice), 404);
    }

    private function assertOwnsOrAdmin(Invoice $invoice, User $user): void
    {
        abort_unless($user->can('update', $invoice), 404);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function invoiceAttributes(array $validated): array
    {
        $services = collect((array) $validated['services'])
            ->map(fn (array $line): array => $this->serviceLineItem(
                $line['name'],
                (int) $line['numberOfSessions'],
                (float) $line['rate_numeric'],
                $line['description'] ?? null,
            ))
            ->all();

        return [
            'client_id' => $validated['client_id'],
            'session_id' => $validated['session_id'] ?? null,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            // No tax_percentage: invoices are raised without GST, and an
            // older invoice that recorded one keeps it through an edit.
            'notes' => $validated['notes'] ?? null,
            'services' => $services,
        ];
    }

    /**
     * @return array{name: string, description: ?string, period: string, numberOfSessions: int, rate: string, rate_numeric: float}
     */
    private function serviceLineItem(string $name, int $numberOfSessions, float $rate, ?string $description = null): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'period' => now()->format('F Y'),
            'numberOfSessions' => $numberOfSessions,
            'rate' => '$'.number_format($rate, 2),
            'rate_numeric' => $rate,
        ];
    }

    /**
     * The `linked_therapist_invoice_id` FK only exists on the admin side —
     * when this new invoice is billed by admin and a therapist invoice
     * already exists for the same session, link to it.
     *
     * @return int<0, max>|null
     */
    /**
     * The therapist bill this clinic invoice recovers, so the admin can see
     * what the family owes them and what they owe the therapist side by side.
     *
     * The admin's explicit choice wins; failing that, a therapist invoice for
     * the same session is matched automatically, which is the common case
     * when both sides bill from one visit. Therapist invoices never link —
     * they are the far end of the chain.
     *
     * @param  array<string, mixed>  $validated
     * @return int<0, max>|null
     */
    private function recoveredTherapistInvoiceId(array $validated, string $billedBy): ?int
    {
        if ($billedBy !== 'admin') {
            return null;
        }

        if (array_key_exists('linked_therapist_invoice_id', $validated)) {
            return $validated['linked_therapist_invoice_id'] !== null
                ? max(0, (int) $validated['linked_therapist_invoice_id'])
                : null;
        }

        $sessionId = $validated['session_id'] ?? null;

        if (! $sessionId) {
            return null;
        }

        $matched = Invoice::query()
            ->where('session_id', $sessionId)
            ->where('billed_by', 'therapist')
            ->value('id');

        return $matched !== null ? max(0, (int) $matched) : null;
    }

    /**
     * Therapist bills the clinic has not settled yet, offered on the admin
     * invoice form so a client invoice can be pointed at the cost behind it.
     *
     * @return Collection<int, Invoice>
     */
    private function recoverableTherapistInvoices(User $user, ?Invoice $invoice = null): Collection
    {
        if (! $user->isAdmin()) {
            return new Collection;
        }

        return Invoice::query()
            ->where('billed_by', 'therapist')
            ->where(function (Builder $selectable) use ($invoice): void {
                $selectable->whereNot('status', 'paid');

                if ($invoice?->linked_therapist_invoice_id !== null) {
                    $selectable->orWhere('id', $invoice->linked_therapist_invoice_id);
                }
            })
            ->with('therapist:id,first_name,last_name')
            ->get(['id', 'invoice_id', 'client_id', 'therapist_id', 'total', 'amount_due', 'status', 'invoice_date']);
    }

    private function routeName(Request $request, string $suffix): string
    {
        $user = $request->user();
        $prefix = $user->isAdmin() ? 'admin' : ($user->isTherapist() ? 'therapist' : 'client');

        return "{$prefix}.{$suffix}";
    }

    /**
     * @return Collection<int, ServiceOffering>
     */
    private function serviceOptions(): Collection
    {
        return ServiceOffering::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'base_price']);
    }

    /**
     * Clients billable from the invoice form: those with at least one session
     * already delivered. A visit counts as delivered once the therapist has
     * clocked out of it — `pending` (awaiting the client's sign-off),
     * `confirmed` (signed off) and `completed` (paid for). Anything merely
     * booked, cancelled or missed has nothing to bill for yet.
     *
     * A therapist only sees clients they themselves have delivered a session
     * to; admins bill for anyone. The invoice being edited keeps its own
     * client selectable so reopening it never renders an empty field.
     *
     * @return Collection<int, Client>
     */
    private function clientOptions(User $user, ?Invoice $invoice = null): Collection
    {
        return Client::query()
            ->where(function (Builder $selectable) use ($user, $invoice): void {
                $selectable->whereHas('sessions', fn (Builder $sessions) => $sessions
                    ->whereIn('status', self::DELIVERED_SESSION_STATUSES)
                    ->when(! $user->isAdmin(), fn (Builder $own) => $own->where('therapist_id', $user->id)));

                if ($invoice?->client_id !== null) {
                    $selectable->orWhere('clients.id', $invoice->client_id);
                }
            })
            ->with('originalIntake:id,child_first_name,child_last_name')
            ->get(['id', 'original_intake_id']);
    }

    /**
     * @return array{id: string, title: string, date: string, time: string}
     */
    private function timelineEntry(string $title): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $title,
            'date' => now()->toDateString(),
            'time' => now()->format('g:i:s A'),
        ];
    }
}
