<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ConsentDocument;
use App\Services\IntakeSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public intake-application flow (reference: src/forms/IntakeApplicationForm.tsx).
 *
 * Submission is refused when the parent's email already has a client account
 * — they're directed to log in and use Client\IntakeController instead, so a
 * second child is created under their existing login rather than stranded
 * (Phase 17).
 */
class IntakeApplicationController extends Controller
{
    public function __construct(private IntakeSubmissionService $intakeSubmissions) {}

    public function create(Request $request): Response
    {
        return Inertia::render('public/intake-application', [
            'requiredConsents' => ConsentDocument::query()
                ->where('purpose', 'intake')
                ->where('is_active', true)
                ->with('clauses')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->intakeSubmissions->rules($request->all(), rejectExistingAccounts: true),
            [],
            $this->intakeSubmissions->attributes(),
        );

        $validator->after(function ($validator) use ($request): void {
            if ($request->input('primary_parent_email') !== $request->input('primary_parent_email_confirm')) {
                $validator->errors()->add('primary_parent_email_confirm', 'Primary emails do not match.');
            }

            $secondaryEmail = $request->input('secondary_parent_email');

            if ($secondaryEmail && $secondaryEmail !== $request->input('secondary_parent_email_confirm')) {
                $validator->errors()->add('secondary_parent_email_confirm', 'Secondary emails do not match.');
            }
        });

        $intake = $this->intakeSubmissions->submit($validator->validate());

        return back()->with([
            'success' => true,
            'reference_number' => $intake->reference_number,
        ]);
    }
}
