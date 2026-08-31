<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTimesheetRequest;
use App\Http\Requests\SignTimesheetRequest;
use App\Mail\TimesheetReadyMail;
use App\Mail\TimesheetSignedAdminNotification;
use App\Models\Client;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ClientContext;
use App\Services\PdfService;
use App\Services\StoredDocumentReader;
use App\Services\TimesheetDocumentService;
use App\Services\TimesheetGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The generated time sheet, reachable from the aide, client, and admin
 * route groups. Data is scoped by the acting user's role rather than by
 * which group was used — the same shared/role-aware shape InvoiceController
 * and SessionController use.
 *
 * The form has three parties: the aide who logged the hours and signs as
 * they generate, the parent who confirms them, and the admin who reads the
 * signed result.
 */
class TimesheetController extends Controller
{
    public function __construct(private ClientContext $clientContext) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = $this->scopedQuery($user);

        $search = trim((string) $request->query('search', ''));
        // An admin is here for the forms both sides have signed, so that is
        // where their list starts; the pending ones stay one click away.
        $status = (string) $request->query('status', $user->isAdmin() ? Timesheet::STATUS_SIGNED : 'all');
        $groupedByClient = ! $user->isClient();

        $timesheets = (clone $baseQuery)
            // Qualified: the grouped list joins `clients` and `intakes`,
            // which both carry a `status` of their own.
            ->when($status !== 'all', fn (Builder $query) => $query->where('timesheets.status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('timesheet_number', 'like', "%{$search}%")
                        ->orWhereHas('client.originalIntake', fn (Builder $child) => $child
                            ->where('child_first_name', 'like', "%{$search}%")
                            ->orWhere('child_last_name', 'like', "%{$search}%"));
                });
            })
            ->with(['client.originalIntake', 'therapist'])
            ->tap(fn (Builder $query) => $groupedByClient
                ? $this->orderByChildName($query)
                : $query->latest('timesheets.id'))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('timesheets/index', [
            'timesheets' => $timesheets,
            // A parent is only ever shown the one child the portal switcher
            // has selected, so grouping them would be a heading over itself.
            'groupedByClient' => $groupedByClient,
            'stats' => [
                'awaiting_client' => (clone $baseQuery)->where('status', Timesheet::STATUS_AWAITING_CLIENT)->count(),
                'signed' => (clone $baseQuery)->where('status', Timesheet::STATUS_SIGNED)->count(),
                'total_hours' => round((float) (clone $baseQuery)->sum('total_hours'), 2),
            ],
            'filters' => ['search' => $search, 'status' => $status],
            'role' => $user->role,
            // Feeds the "Generate Timesheet" modal. Aides only — nobody else
            // raises a form out of logged hours.
            'sheetableClients' => $user->isAide() ? $this->clientsWithUnsheetedHours($user) : [],
        ]);
    }

    public function show(Request $request, Timesheet $timesheet): Response
    {
        abort_unless($request->user()->can('view', $timesheet), 404);

        $timesheet->load(['client.originalIntake', 'therapist']);

        return Inertia::render('timesheets/show', [
            'timesheet' => $timesheet,
            'role' => $request->user()->role,
        ]);
    }

    /**
     * Raises a time sheet for every hour logged over a chosen period, from
     * the "Generate Timesheet" modal on the timesheets list.
     *
     * The aide signs as they generate, so the form goes straight out to the
     * family for their half of the signature.
     */
    public function generate(GenerateTimesheetRequest $request, TimesheetGenerator $generator): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $client = Client::query()->findOrFail((int) $validated['client_id']);

        $timesheet = $generator->generate(
            $user,
            $client,
            Carbon::parse($validated['date_start']),
            Carbon::parse($validated['date_end']),
            $validated['signature'],
        );

        if ($timesheet === null) {
            throw ValidationException::withMessages([
                'client_id' => 'You have logged no hours for that client between those dates.',
            ]);
        }

        $this->emailParent($timesheet);

        AuditLogger::log(
            'Generated timesheet',
            'Timesheets',
            "Generated timesheet {$timesheet->timesheet_number} for client #{$client->id}",
        );

        return to_route($this->routeName($request, 'timesheets.show'), $timesheet)
            ->with('success', 'Timesheet generated and sent to the parent.');
    }

    /**
     * The form itself: the copy the parent signed when there is one,
     * otherwise the PDF filed when it was generated.
     */
    public function pdf(Request $request, Timesheet $timesheet, PdfService $pdfService): HttpResponse
    {
        abort_unless($request->user()->can('view', $timesheet), 404);

        $stored = $timesheet->signed_timesheet ?? $timesheet->not_signed_timesheet;
        $filename = 'timesheet-'.($timesheet->timesheet_number ?? $timesheet->id).'.pdf';

        $contents = filled($stored) ? app(StoredDocumentReader::class)->contents($stored) : null;

        // Nothing on file (or it could not be read) — render the form from
        // the record as it stands rather than showing the viewer an error.
        $contents ??= $pdfService->timesheet($timesheet);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            // The modal frames this on our own origin.
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    /**
     * The parent returns the form with their signature drawn into the
     * signature box. The signed copy is filed separately — the unsigned
     * original stays exactly as the aide generated it.
     */
    public function sign(SignTimesheetRequest $request, Timesheet $timesheet, TimesheetDocumentService $documents): RedirectResponse
    {
        abort_unless($request->user()->can('sign', $timesheet), 404);

        $signature = $request->validated()['signature'];

        // Filed first: the signed PDF is the record, so a Drive failure must
        // not leave a timesheet marked signed with no document behind it.
        $stored = $documents->storeSigned($timesheet, $signature);

        if ($stored === null) {
            return back()->with('error', 'We could not file your signed timesheet. Please try again.');
        }

        $timesheet->forceFill([
            'parent_signature' => $signature,
            'parent_signed_at' => now(),
            'status' => Timesheet::STATUS_SIGNED,
            'timeline' => [
                ...($timesheet->timeline ?? []),
                $this->timelineEntry('Timesheet signed by parent'),
            ],
        ])->save();

        AuditLogger::log(
            'Timesheet signed',
            'Timesheets',
            "Timesheet {$timesheet->timesheet_number} signed by the parent",
            'success',
            $request->user()->email,
        );

        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails)->send(new TimesheetSignedAdminNotification($timesheet));
        }

        return back()->with('success', 'Thank you — your signed timesheet has been sent.');
    }

    public function destroy(Request $request, Timesheet $timesheet): RedirectResponse
    {
        abort_unless($request->user()->can('delete', $timesheet), 404);

        // The hours themselves outlive the form: releasing them lets the
        // aide sheet the period again rather than losing the work.
        $timesheet->entries()->update(['timesheet_id' => null]);
        $timesheet->delete();

        AuditLogger::log(
            'Deleted timesheet',
            'Timesheets',
            "Deleted timesheet {$timesheet->timesheet_number}",
            'warning',
        );

        return to_route('admin.timesheets.index')->with('success', 'Timesheet removed.');
    }

    /**
     * Orders the list so a family's forms arrive together and the groups
     * read alphabetically.
     *
     * The list is paginated, so grouping in the browser alone would split a
     * family across two pages whenever the page break fell mid-child. The
     * child's name lives on the intake, so it is joined rather than sorted
     * on `client_id` — an id order would group correctly but shuffle the
     * headings into no readable order.
     *
     * @param  Builder<Timesheet>  $query
     * @return Builder<Timesheet>
     */
    private function orderByChildName(Builder $query): Builder
    {
        return $query
            ->leftJoin('clients', 'clients.id', '=', 'timesheets.client_id')
            ->leftJoin('intakes', 'intakes.id', '=', 'clients.original_intake_id')
            // Without this the joined columns overwrite the timesheet's own
            // `id` on the hydrated model.
            ->select('timesheets.*')
            ->orderBy('intakes.child_first_name')
            ->orderBy('intakes.child_last_name')
            ->orderBy('timesheets.client_id')
            // Newest first within each child.
            ->orderByDesc('timesheets.id');
    }

    /**
     * Whose timesheets a user sees.
     *
     * An aide sees their own; a parent sees the child the portal switcher
     * currently has selected; an admin sees every form the clinic holds.
     *
     * @return Builder<Timesheet>
     */
    private function scopedQuery(User $user): Builder
    {
        if ($user->isAdmin()) {
            return Timesheet::query();
        }

        if ($user->isTherapist()) {
            return Timesheet::query()->where('therapist_id', $user->id);
        }

        // The list follows the portal switcher — one child at a time.
        return Timesheet::query()->where('client_id', $this->clientContext->currentId($user) ?? 0);
    }

    /**
     * Clients this aide has hours logged against that no timesheet has
     * claimed — the only ones worth offering in the generate modal.
     *
     * @return Collection<int, Client>
     */
    private function clientsWithUnsheetedHours(User $aide): Collection
    {
        return Client::query()
            ->whereIn('id', TimesheetGenerator::claimableFor($aide)->select('client_id'))
            ->with('originalIntake:id,child_first_name,child_last_name')
            ->get(['id', 'original_intake_id']);
    }

    private function emailParent(Timesheet $timesheet): void
    {
        $timesheet->loadMissing(['client.user', 'client.originalIntake']);
        $client = $timesheet->client;

        // `??` reads with isset() semantics, so an absent relation is simply
        // the next candidate rather than a read on null.
        $recipient = $client->user->email ?? $client->originalIntake->primary_parent_email ?? null;

        if ($recipient !== null) {
            Mail::to($recipient)->send(new TimesheetReadyMail($timesheet));
        }
    }

    private function routeName(Request $request, string $suffix): string
    {
        return $request->user()->isAdmin() ? "admin.{$suffix}" : "therapist.{$suffix}";
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
