<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\IntakeSubmittedAdminNotification;
use App\Mail\IntakeSubmittedConfirmationMail;
use App\Models\ConsentDocument;
use App\Models\Intake;
use App\Models\User;
use App\Models\UserConsentAcceptance;
use App\Services\AuditLogger;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\PdfService;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public intake-application flow (reference: src/forms/IntakeApplicationForm.tsx).
 */
class IntakeApplicationController extends Controller
{
    /**
     * FSCD funding-source variants that require the FSCD sub-form
     * and hardcoded FSCD consents in funding_source_info.
     *
     * @var array<int, string>
     */
    private const FSCD_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

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

    public function store(Request $request, PdfService $pdfService, DriveStorage $drive): RedirectResponse
    {
        $fundingSourceInput = (string) $request->input('funding_source');
        $isFscd = in_array($fundingSourceInput, self::FSCD_SOURCES, true);
        $isInsurance = $fundingSourceInput === 'Insurance';

        $validator = Validator::make($request->all(), [
            'child_first_name' => ['required', 'string', 'max:100'],
            'child_middle_name' => ['nullable', 'string', 'max:100'],
            'child_last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'street_address' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state_province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:7'],
            'grade_level' => ['nullable', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],

            'services_needed' => ['array'],
            'services_needed.*' => ['string'],
            'currently_receiving_services' => ['boolean'],
            'receiving_services_desc' => ['nullable', 'string'],
            'diagnosis' => ['array'],
            'diagnosis.*' => ['string'],
            'has_medical_conditions' => ['boolean'],
            'languages_spoken_at_home' => ['nullable', 'string', 'max:255'],
            'require_interpreter' => ['boolean'],
            'interpreter_needed' => ['nullable', 'string', 'max:255'],
            'medical_conditions' => ['nullable', 'string'],

            'funding_source' => ['required', Rule::in([...self::FSCD_SOURCES, 'Insurance', 'private'])],
            'available_days' => ['array'],
            'available_days.*' => ['string'],
            'preferred_times' => ['array'],
            'preferred_times.*' => ['string'],

            'primary_parent_name' => ['required', 'string', 'max:255'],
            'primary_parent_phone' => ['required', 'string', 'max:20'],
            'primary_parent_email' => ['required', 'email', 'max:255'],
            'primary_parent_email_confirm' => ['required', 'email', 'max:255'],
            'primary_relationship_to_child' => ['required', 'string', 'max:255'],
            'primary_contact_method' => ['required', 'string', 'max:255'],

            'secondary_parent_name' => ['nullable', 'string', 'max:255'],
            'secondary_parent_phone' => ['nullable', 'string', 'max:20'],
            'secondary_parent_email' => ['nullable', 'email', 'max:255'],
            'secondary_parent_email_confirm' => ['nullable', 'email', 'max:255'],
            'secondary_relationship_to_child' => ['nullable', 'string', 'max:255'],
            'secondary_contact_method' => ['nullable', 'string', 'max:255'],

            'additional_information' => ['nullable', 'string'],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'emergency_contact_relationship' => ['required', 'string', 'max:255'],

            'fscd_info' => ['array'],
            'fscd_info.FSCD_case_worker_name' => [Rule::requiredIf($isFscd), 'nullable', 'string', 'max:255'],
            'fscd_info.FSCD_case_worker_email' => [Rule::requiredIf($isFscd), 'nullable', 'email', 'max:255'],
            'fscd_info.FSCD_approval_start_date' => [Rule::requiredIf($isFscd), 'nullable', 'date'],
            'fscd_info.FSCD_approval_end_date' => ['nullable', 'date'],

            'insurance_info' => ['array'],
            'insurance_info.insurance_provider' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.policy_number' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.certificate_number' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.policy_holder_name' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.policy_holder_date_of_birth' => [Rule::requiredIf($isInsurance), 'nullable', 'date'],
            'insurance_info.pre_authorization_obtained' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:255'],
            'insurance_info.used_annual_maximum' => ['nullable', 'string', 'max:255'],
            'insurance_info.authorization_start_date' => ['nullable', 'date'],
            'insurance_info.authorization_end_date' => ['nullable', 'date'],

            'referral_source' => ['required', 'string', 'max:255'],
            'referral_source_other' => ['nullable', 'string', 'max:255'],

            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
            'consent_ids' => ['array'],
            'consent_ids.*' => ['integer', 'exists:consent_documents,id'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($request->input('primary_parent_email') !== $request->input('primary_parent_email_confirm')) {
                $validator->errors()->add('primary_parent_email_confirm', 'Primary emails do not match.');
            }

            $secondaryEmail = $request->input('secondary_parent_email');

            if ($secondaryEmail && $secondaryEmail !== $request->input('secondary_parent_email_confirm')) {
                $validator->errors()->add('secondary_parent_email_confirm', 'Secondary emails do not match.');
            }
        });

        $validated = $validator->validate();

        $dateOfBirth = Carbon::parse($validated['date_of_birth']);

        $referralSource = $validated['referral_source'] === 'Other' && ! empty($validated['referral_source_other'])
            ? $validated['referral_source_other']
            : $validated['referral_source'];

        $intake = Intake::query()->create([
            'child_first_name' => $validated['child_first_name'],
            'child_middle_name' => $validated['child_middle_name'] ?? null,
            'child_last_name' => $validated['child_last_name'],
            'date_of_birth' => $dateOfBirth,
            'age' => $dateOfBirth->age,
            'gender' => $validated['gender'] ?? null,
            'status' => 'pending',
            'street_address' => $validated['street_address'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'],
            'state_province' => $validated['state_province'],
            'postal_code' => $validated['postal_code'],
            'grade_level' => $validated['grade_level'] ?? null,
            'school_name' => $validated['school_name'] ?? null,
            'services_needed' => $validated['services_needed'] ?? [],
            'currently_receiving_services' => $validated['currently_receiving_services'] ?? false,
            'receiving_services_desc' => $validated['receiving_services_desc'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? [],
            'has_medical_conditions' => $validated['has_medical_conditions'] ?? false,
            'languages_spoken_at_home' => $validated['languages_spoken_at_home'] ?? null,
            'require_interpreter' => $validated['require_interpreter'] ?? false,
            'interpreter_needed' => $validated['interpreter_needed'] ?? null,
            'medical_conditions' => $validated['medical_conditions'] ?? null,
            'funding_source' => $validated['funding_source'],
            'available_days' => $validated['available_days'] ?? [],
            'preferred_times' => $validated['preferred_times'] ?? [],
            'primary_parent_name' => $validated['primary_parent_name'],
            'primary_parent_phone' => $validated['primary_parent_phone'],
            'primary_parent_email' => $validated['primary_parent_email'],
            'primary_relationship_to_child' => $validated['primary_relationship_to_child'],
            'primary_contact_method' => $validated['primary_contact_method'],
            'secondary_parent_name' => $validated['secondary_parent_name'] ?? null,
            'secondary_parent_phone' => $validated['secondary_parent_phone'] ?? null,
            'secondary_parent_email' => $validated['secondary_parent_email'] ?? null,
            'secondary_relationship_to_child' => $validated['secondary_relationship_to_child'] ?? null,
            'secondary_contact_method' => $validated['secondary_contact_method'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
            'referral_source' => $referralSource,
            'emergency_contact_name' => $validated['emergency_contact_name'],
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'],
            'emergency_contact_phone' => $validated['emergency_contact_phone'],
            'funding_source_info' => $this->buildFundingSourceInfo($validated),
            'timeline' => [
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'Intake form submitted via website',
                    'date' => now()->toDateString(),
                    'time' => now()->format('g:i:s A'),
                ],
            ],
            'reference_number' => app(ReferenceNumberGenerator::class)->intake(),
            'consents' => $validated['consent_ids'] ?? [],
        ]);

        foreach ($validated['consent_ids'] ?? [] as $documentId) {
            UserConsentAcceptance::query()->updateOrCreate(
                ['document_id' => $documentId, 'user_id' => null],
                ['accepted_at' => now(), 'is_revoked' => false, 'revoked_at' => null],
            );
        }

        $this->uploadConsentPdf($intake, $pdfService, $drive);

        if ($intake->primary_parent_email !== null) {
            Mail::to($intake->primary_parent_email)->send(new IntakeSubmittedConfirmationMail($intake));
        }

        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where('new_intake', true)
            ->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails)->send(new IntakeSubmittedAdminNotification($intake));
        }

        AuditLogger::log(
            'Intake submitted',
            'Intake',
            "New public intake submission #{$intake->id} ({$intake->reference_number})",
            'info',
            $intake->primary_parent_email,
        );

        return back()->with([
            'success' => true,
            'reference_number' => $intake->reference_number,
        ]);
    }

    /**
     * Reference: cats-backend/cats/views.py:207 — generates and uploads the
     * consent PDF right after intake creation, filed under the same
     * IntakeDocuments/{intake folder} Drive location as manually-uploaded
     * intake documents.
     */
    private function uploadConsentPdf(Intake $intake, PdfService $pdfService, DriveStorage $drive): void
    {
        $pdfContents = $pdfService->consentPdfForIntake($intake);

        if ($pdfContents === null) {
            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'consent-pdf-');
        file_put_contents($tempPath, $pdfContents);

        $filename = "Consents-{$intake->reference_number}-{$intake->child_first_name}-{$intake->child_last_name}.pdf";
        $file = new UploadedFile($tempPath, $filename, 'application/pdf', null, true);

        $uploaded = $drive->upload($file, 'client', $this->intakeFolderName($intake));

        $intake->documents()->create([
            'name' => $filename,
            'type' => 'Consent Forms',
            ...$uploaded,
            'uploaded_at' => now(),
        ]);
    }

    private function intakeFolderName(Intake $intake): string
    {
        $name = trim("{$intake->child_first_name} {$intake->child_last_name}");

        return trim("{$intake->id}_{$name}", '_');
    }

    /**
     * Merges the nested fscd_info/insurance_info sub-objects from the
     * reference schema into the flat funding_source_info JSON column,
     * exactly matching the reference's onSubmit payload shape.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildFundingSourceInfo(array $validated): array
    {
        $fundingSource = $validated['funding_source'];
        $isFscd = in_array($fundingSource, self::FSCD_SOURCES, true);

        $info = match (true) {
            $fundingSource === 'Insurance' => $validated['insurance_info'] ?? [],
            $isFscd => $validated['fscd_info'] ?? [],
            default => [],
        };

        $info['consents'] = $isFscd
            ? [
                ['title' => 'FSCD Worker Communication', 'datetime' => now()->toIso8601String()],
                ['title' => 'Reports Sharing', 'datetime' => now()->toIso8601String()],
                ['title' => 'Non-Approved Costs Acknowledgment', 'datetime' => now()->toIso8601String()],
            ]
            : [];

        return $info;
    }
}
