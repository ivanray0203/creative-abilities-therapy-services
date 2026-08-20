<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\User;
use App\Services\ClientContext;
use App\Services\IntakeSubmissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets a signed-in parent register an additional child (Phase 17).
 *
 * The public form refuses an email that already has an account and points
 * here instead, so every child a parent enrols stays under one login. Once
 * approved, IntakeApprovalService::promote() gives the new child its own
 * Client record attached to that same parent User.
 */
class IntakeController extends Controller
{
    public function __construct(
        private IntakeSubmissionService $intakeSubmissions,
        private ClientContext $clientContext,
    ) {}

    /**
     * Every intake this parent has submitted, whichever route it came in by.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $intakes = $this->ownIntakes($request->user())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('child_first_name', 'like', "%{$search}%")
                        ->orWhere('child_last_name', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('client/intake/index', [
            'intakes' => $intakes,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $existing = $this->clientContext->current($user)?->originalIntake;

        return Inertia::render('client/intake/create', [
            'requiredConsents' => ConsentDocument::query()
                ->where('purpose', 'intake')
                ->where('is_active', true)
                ->with('clauses')
                ->get(),
            // Carried over from a child already in care so the parent only
            // fills in what differs — the new child's own details.
            'prefill' => [
                'primary_parent_name' => $existing?->primary_parent_name ?: $user->full_name,
                'primary_parent_phone' => $existing?->primary_parent_phone ?: $user->phone,
                'primary_parent_email' => $user->email,
                'primary_parent_email_confirm' => $user->email,
                'primary_relationship_to_child' => $existing?->primary_relationship_to_child,
                'primary_contact_method' => $existing?->primary_contact_method,
                'street_address' => $existing?->street_address,
                'address_line_2' => $existing?->address_line_2,
                'city' => $existing?->city,
                'state_province' => $existing?->state_province,
                'postal_code' => $existing?->postal_code,
                'emergency_contact_name' => $existing?->emergency_contact_name,
                'emergency_contact_phone' => $existing?->emergency_contact_phone,
                'emergency_contact_relationship' => $existing?->emergency_contact_relationship,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validator = Validator::make(
            $request->all(),
            // The account check is skipped here — having an account is the
            // precondition for this route, not a reason to refuse.
            $this->intakeSubmissions->rules($request->all(), rejectExistingAccounts: false),
            [],
            $this->intakeSubmissions->attributes(),
        );

        $validator->after(function ($validator) use ($request, $user): void {
            // The intake must stay attached to the signed-in parent, otherwise
            // approval would spin up a second account for the same family.
            if ($request->input('primary_parent_email') !== $user->email) {
                $validator->errors()->add(
                    'primary_parent_email',
                    'A new intake must use the email address on your account.',
                );
            }

            $secondaryEmail = $request->input('secondary_parent_email');

            if ($secondaryEmail && $secondaryEmail !== $request->input('secondary_parent_email_confirm')) {
                $validator->errors()->add('secondary_parent_email_confirm', 'Secondary emails do not match.');
            }
        });

        $intake = $this->intakeSubmissions->submit($validator->validate(), $user);

        return back()->with([
            'success' => true,
            'reference_number' => $intake->reference_number,
        ]);
    }

    /**
     * The parent's intakes.
     *
     * Matched on `primary_parent_email` rather than `submitted_by_id` alone,
     * because their first child's intake was submitted publicly — before the
     * account existed — and would otherwise be missing from a list that shows
     * that child everywhere else in the portal.
     *
     * @return Builder<Intake>
     */
    private function ownIntakes(User $user): Builder
    {
        return Intake::query()->where(function (Builder $query) use ($user): void {
            $query->where('primary_parent_email', $user->email)
                ->orWhere('submitted_by_id', $user->id);
        });
    }
}
