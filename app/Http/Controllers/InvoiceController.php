<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceResendMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = $this->scopedQuery($user);

        $quick = (string) $request->query('quick', 'all');
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $filtered = (clone $baseQuery)
            ->when($quick === 'paid', fn (Builder $query) => $query->where('status', 'paid'))
            ->when($quick === 'unpaid', fn (Builder $query) => $query->whereIn('status', ['sent', 'unpaid']))
            ->when($quick === 'overdue', fn (Builder $query) => $query->where('status', 'overdue'))
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
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
        ];

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => ['quick' => $quick, 'search' => $search, 'status' => $status],
            'role' => $user->role,
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->assertCanView($invoice, $request->user());

        $invoice->load(['client.originalIntake', 'therapist', 'session', 'linkedTherapistInvoice', 'linkedAdminInvoices']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'role' => $request->user()->role,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('invoices/create', [
            'role' => $request->user()->role,
            'clients' => $this->clientOptions(),
            'services' => $this->serviceOptions(),
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
            'invoice_id' => $referenceNumberGenerator->invoice(),
            'status' => $validated['action'] === 'send' ? 'sent' : 'draft',
            'issued_by_id' => $user->id,
            'timeline' => [$this->timelineEntry('Invoice created')],
        ]);

        $invoice->calculateTotals();
        $invoice->linked_therapist_invoice_id = $this->linkedTherapistInvoiceId($validated['session_id'] ?? null, $billedBy);
        $invoice->save();

        if ($invoice->status === 'sent') {
            $this->emailInvoice($invoice);
        }

        AuditLogger::log('Created invoice', 'Invoices', "Created invoice {$invoice->invoice_id}");

        return to_route($this->routeName($request, 'invoices.show'), $invoice)->with('success', 'Invoice created successfully.');
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->assertOwnsOrAdmin($invoice, $request->user());

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'role' => $request->user()->role,
            'clients' => $this->clientOptions(),
            'services' => $this->serviceOptions(),
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

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        AuditLogger::log('Deleted invoice', 'Invoices', "Deleted invoice {$invoice->invoice_id}", 'warning');

        return to_route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function generateFromSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:schedule_sessions,id'],
        ]);

        $session = ScheduleSession::query()->with(['client', 'service', 'linkedClientService.service'])->findOrFail((int) $validated['session_id']);

        if ($session->status !== 'completed') {
            throw ValidationException::withMessages([
                'session_id' => 'Only completed sessions can be invoiced.',
            ]);
        }

        $user = $request->user();
        $billedBy = $user->isAdmin() ? 'admin' : 'therapist';
        $client = $session->client;

        $linkedService = optional(optional($session->linkedClientService)->service)->base_price;
        $directService = optional($session->service);
        $rate = (float) ($linkedService ?? $directService->base_price ?? 0);
        $serviceName = $directService->name ?? $session->service_name ?? 'Session';

        $invoice = new Invoice([
            'client_id' => $client->id,
            'billing_account_id' => $client->billing?->id,
            'session_id' => $session->id,
            'therapist_id' => $user->isAdmin() ? $session->therapist_id : $user->id,
            'billed_by' => $billedBy,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_percentage' => 5.00,
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
        $invoice->linked_therapist_invoice_id = $this->linkedTherapistInvoiceId($session->id, $billedBy);
        $invoice->save();

        AuditLogger::log('Generated invoice from session', 'Invoices', "Generated invoice {$invoice->invoice_id} from session #{$session->id}");

        return to_route($this->routeName($request, 'invoices.show'), $invoice)->with('success', 'Invoice generated from session.');
    }

    public function markPaid(Invoice $invoice): RedirectResponse
    {
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
    private function emailInvoice(Invoice $invoice): void
    {
        $client = optional($invoice->client);
        $recipientEmail = optional($client->user)->email ?? optional($client->originalIntake)->primary_parent_email;

        if ($recipientEmail !== null) {
            Mail::to($recipientEmail)->send(new InvoiceResendMail($invoice));
        }
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
            return Invoice::query()->where('therapist_id', $user->id);
        }

        $clientId = $user->clientProfile !== null ? $user->clientProfile->id : 0;

        return Invoice::query()->where('client_id', $clientId);
    }

    private function assertCanView(Invoice $invoice, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTherapist() && $invoice->therapist_id === $user->id) {
            return;
        }

        if ($user->isClient() && $invoice->client_id === $user->clientProfile?->id) {
            return;
        }

        abort(404);
    }

    private function assertOwnsOrAdmin(Invoice $invoice, User $user): void
    {
        if (! $user->isAdmin() && $invoice->therapist_id !== $user->id) {
            abort(404);
        }
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
            'tax_percentage' => $validated['tax_percentage'] ?? 5.00,
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
    private function linkedTherapistInvoiceId(?int $sessionId, string $billedBy): ?int
    {
        if ($billedBy !== 'admin' || ! $sessionId) {
            return null;
        }

        return Invoice::query()
            ->where('session_id', $sessionId)
            ->where('billed_by', 'therapist')
            ->value('id');
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
     * @return Collection<int, Client>
     */
    private function clientOptions(): Collection
    {
        return Client::query()
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
