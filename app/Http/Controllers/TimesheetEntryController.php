<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimesheetEntryRequest;
use App\Models\TimesheetEntry;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BillingFormOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hours, as the aide sees them: one row per child per day, logged as the
 * work happens. Nothing is sheeted here — the rows sit unclaimed until the
 * aide generates a timesheet for a period, at which point `timesheet_id` is
 * filled in and the row is frozen.
 *
 * The aide's counterpart to BillingItemController, minus the money: an aide
 * records hours, and what those hours are worth is settled off the signed
 * form by the clinic rather than priced here.
 *
 * Aides only — the route group carries the `aide` middleware, so a
 * therapist who bills services never reaches this.
 */
class TimesheetEntryController extends Controller
{
    public function __construct(private BillingFormOptions $formOptions) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $baseQuery = TimesheetEntry::query()->where('therapist_id', $user->id);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $entries = (clone $baseQuery)
            ->when($status === 'sheeted', fn (Builder $query) => $query->whereNotNull('timesheet_id'))
            ->when($status === 'unsheeted', fn (Builder $query) => $query->whereNull('timesheet_id'))
            ->when($search !== '', fn (Builder $query) => $query->whereHas(
                'client.originalIntake',
                fn (Builder $child) => $child->where('child_first_name', 'like', "%{$search}%")
                    ->orWhere('child_last_name', 'like', "%{$search}%"),
            ))
            ->with(['client.originalIntake', 'timesheet:id,timesheet_number'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('hours/index', [
            'entries' => $entries,
            'stats' => $this->stats($user),
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('hours/create', [
            'clients' => $this->formOptions->clients($request->user()),
        ]);
    }

    /**
     * Saves one row per day on the form. A day already logged for this child
     * is corrected rather than duplicated — the unique key on
     * (therapist, client, date) is what the printed grid assumes.
     */
    public function store(StoreTimesheetEntryRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $clientId = (int) $validated['client_id'];

        /** @var array<int, array<string, mixed>> $entries */
        $entries = $validated['entries'];

        DB::transaction(function () use ($entries, $clientId, $user): void {
            foreach ($entries as $entry) {
                TimesheetEntry::query()->updateOrCreate(
                    [
                        'therapist_id' => $user->id,
                        'client_id' => $clientId,
                        // Matched as a date, not as the string the form sent:
                        // `updateOrCreate` builds its lookup without the
                        // model's casts, so a raw "2026-08-03" would miss the
                        // stored "2026-08-03 00:00:00" and try to insert a
                        // second row the unique key then rejects.
                        'entry_date' => Carbon::parse($entry['entry_date'])->startOfDay(),
                    ],
                    [
                        'hourly_respite_hours' => (float) ($entry['hourly_respite_hours'] ?? 0),
                        'community_support_hours' => (float) ($entry['community_support_hours'] ?? 0),
                        'bda_direct_hours' => (float) ($entry['bda_direct_hours'] ?? 0),
                        'bda_indirect_hours' => (float) ($entry['bda_indirect_hours'] ?? 0),
                        'notes' => $entry['notes'] ?? null,
                    ],
                );
            }
        });

        $count = count($entries);

        AuditLogger::log(
            'Logged hours',
            'Timesheets',
            "Logged {$count} day(s) of hours for client #{$clientId}",
        );

        return to_route('therapist.hours.index')
            ->with('success', "{$count} day(s) of hours saved.");
    }

    /**
     * Hours can be corrected right up until a timesheet claims them; after
     * that the row is what the parent signed for, so it stays put.
     */
    public function destroy(Request $request, TimesheetEntry $entry): RedirectResponse
    {
        abort_unless($entry->therapist_id === $request->user()->id, 404);

        if ($entry->timesheet_id !== null) {
            return back()->with('error', 'These hours are already on a timesheet and can no longer be removed.');
        }

        $entry->delete();

        AuditLogger::log(
            'Deleted logged hours',
            'Timesheets',
            "Deleted hours for {$entry->entry_date?->toDateString()}",
            'warning',
        );

        return back()->with('success', 'Hours removed.');
    }

    /**
     * What the aide has logged and how much of it is still waiting for a
     * timesheet — the hours equivalent of the billing ledger's totals.
     *
     * @return array<string, float|int>
     */
    private function stats(User $user): array
    {
        // The form's right-hand total: all four columns added together.
        $allColumns = DB::raw(
            'hourly_respite_hours + community_support_hours + bda_direct_hours + bda_indirect_hours'
        );

        $mine = fn (): Builder => TimesheetEntry::query()->where('therapist_id', $user->id);

        return [
            'unsheeted_hours' => round((float) $mine()->whereNull('timesheet_id')->sum($allColumns), 2),
            'unsheeted_days' => $mine()->whereNull('timesheet_id')->count(),
            'sheeted_hours' => round((float) $mine()->whereNotNull('timesheet_id')->sum($allColumns), 2),
            'this_month_hours' => round((float) $mine()
                ->whereMonth('entry_date', now()->month)
                ->whereYear('entry_date', now()->year)
                ->sum($allColumns), 2),
        ];
    }
}
