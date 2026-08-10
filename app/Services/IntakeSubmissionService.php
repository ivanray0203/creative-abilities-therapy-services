<?php

namespace App\Services;

use App\Mail\IntakeSubmittedAdminNotification;
use App\Mail\IntakeSubmittedConfirmationMail;
use App\Models\Intake;
use App\Models\User;
use App\Models\UserConsentAcceptance;
use App\Services\GoogleDrive\DriveStorage;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared intake submission pipeline.
 *
 * Backs both the public form (Public\IntakeApplicationController) and the
 * authenticated "register another child" form a signed-in parent uses
 * (Client\IntakeController, Phase 17). The two differ only in whether the
 * parent's email may already have an account, and whether the resulting
 * intake records who submitted it.
 */
class IntakeSubmissionService
{
    /**
     * FSCD funding-source variants that require the FSCD sub-form
     * and hardcoded FSCD consents in funding_source_info.
     *
     * @var array<int, string>
     */
    public const FSCD_SOURCES = ['BDS-FSCD', 'SS-FSCD', 'Counselling-FSCD'];

    public function __construct(
        private PdfService $pdfService,
        private DriveStorage $drive,
        private ReferenceNumberGenerator $referenceNumbers,
    ) {}

    /**
     * Validation rules for an intake submission.
     *
     * @param  array<string, mixed>  $input
     * @param  bool  $rejectExistingAccounts  Public submissions are refused
     *                                        when the parent already has an
     *                                        account — they're asked to log in
     *                                        and use the authenticated form
     *                                        instead, so their children stay
     *                                        under one login.
     * @return array<string, mixed>
     */
    public function rules(array $input, bool $rejectExistingAccounts): array
    {
        $fundingSource = (string) ($input['funding_source'] ?? '');
        $isFscd = in_array($fundingSource, self::FSCD_SOURCES, true);
        $isInsurance = $fundingSource === 'Insurance';

        $emailRules = ['required', 'email', 'max:255'];

        if ($rejectExistingAccounts) {
            $emailRules[] = $this->rejectExistingAccount();
        }

        return [
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
            'primary_parent_email' => $emailRules,
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
        ];
    }

    /**
     * Readable field names for the validator.
     *
     * Without these the nested funding-source keys produce messages like
     * "The fscd info. f s c d approval start date field is required", which
     * an applicant can make no sense of.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'child_first_name' => "child's first name",
            'child_middle_name' => "child's middle name",
            'child_last_name' => "child's last name",
            'date_of_birth' => 'date of birth',
            'state_province' => 'province',
            'postal_code' => 'postal code',
            'services_needed' => 'services needed',
            'currently_receiving_services' => 'currently receiving services',
            'receiving_services_desc' => 'description of current services',
            'has_medical_conditions' => 'medical conditions',
            'languages_spoken_at_home' => 'languages spoken at home',
            'require_interpreter' => 'interpreter required',
            'interpreter_needed' => 'interpreter language',
            'funding_source' => 'funding source',
            'available_days' => 'available days',
            'preferred_times' => 'preferred times',
            'primary_parent_name' => "primary parent's name",
            'primary_parent_phone' => "primary parent's phone",
            'primary_parent_email' => "primary parent's email",
            'primary_parent_email_confirm' => "primary parent's confirmed email",
            'primary_relationship_to_child' => 'relationship to child',
            'primary_contact_method' => 'preferred contact method',
            'secondary_parent_name' => "secondary parent's name",
            'secondary_parent_phone' => "secondary parent's phone",
            'secondary_parent_email' => "secondary parent's email",
            'secondary_parent_email_confirm' => "secondary parent's confirmed email",
            'secondary_relationship_to_child' => "secondary parent's relationship to child",
            'secondary_contact_method' => "secondary parent's contact method",
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
            'emergency_contact_relationship' => 'emergency contact relationship',
            'fscd_info.FSCD_case_worker_name' => 'FSCD case worker name',
            'fscd_info.FSCD_case_worker_email' => 'FSCD case worker email',
            'fscd_info.FSCD_approval_start_date' => 'FSCD approval start date',
            'fscd_info.FSCD_approval_end_date' => 'FSCD approval end date',
            'insurance_info.insurance_provider' => 'insurance provider',
            'insurance_info.policy_number' => 'policy number',
            'insurance_info.certificate_number' => 'certificate number',
            'insurance_info.policy_holder_name' => 'policy holder name',
            'insurance_info.policy_holder_date_of_birth' => "policy holder's date of birth",
            'insurance_info.pre_authorization_obtained' => 'pre-authorization obtained',
            'insurance_info.used_annual_maximum' => 'annual maximum used',
            'insurance_info.authorization_start_date' => 'authorization start date',
            'insurance_info.authorization_end_date' => 'authorization end date',
            'referral_source' => 'referral source',
            'referral_source_other' => 'other referral source',
            'terms_accepted' => 'terms of service',
            'privacy_accepted' => 'privacy policy',
            'consent_ids' => 'consents',
        ];
    }

    /**
     * Create the intake, record consents, file the consent PDF, and send the
     * confirmation/notification emails.
     *
     * @param  array<string, mixed>  $validated
     * @param  User|null  $submittedBy  Set when a signed-in parent registers
     *                                  an additional child.
     */
    public function submit(array $validated, ?User $submittedBy = null): Intake
    {
        $dateOfBirth = Carbon::parse($validated['date_of_birth']);

        $referralSource = $validated['referral_source'] === 'Other' && ! empty($validated['referral_source_other'])
            ? $validated['referral_source_other']
            : $validated['referral_source'];

        $intake = Intake::query()->create([
            'submitted_by_id' => $submittedBy?->id,
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
                    'title' => $submittedBy !== null
                        ? 'Intake form submitted from client portal'
                        : 'Intake form submitted via website',
                    'date' => now()->toDateString(),
                    'time' => now()->format('g:i:s A'),
                ],
            ],
            'reference_number' => $this->referenceNumbers->intake(),
            'consents' => $validated['consent_ids'] ?? [],
        ]);

        foreach ($validated['consent_ids'] ?? [] as $documentId) {
            UserConsentAcceptance::query()->updateOrCreate(
                ['document_id' => $documentId, 'user_id' => $submittedBy?->id],
                ['accepted_at' => now(), 'is_revoked' => false, 'revoked_at' => null],
            );
        }

        $this->uploadConsentPdf($intake);
        $this->sendNotifications($intake);

        AuditLogger::log(
            'Intake submitted',
            'Intake',
            "New intake submission #{$intake->id} ({$intake->reference_number})",
            'info',
            $intake->primary_parent_email,
        );

        return $intake;
    }

    /**
     * Refuses an email that already belongs to a client account, steering the
     * parent to log in so a second child lands under their existing login
     * instead of creating a stranded duplicate.
     */
    private function rejectExistingAccount(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $hasAccount = User::query()
                ->where('email', $value)
                ->where('role', 'client')
                ->exists();

            if ($hasAccount) {
                $fail('You already have an account. Please log in to register a new intake for another child.');
            }
        };
    }

    private function sendNotifications(Intake $intake): void
    {
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
    }

    /**
     * Reference: cats-backend/cats/views.py:207 — generates and uploads the
     * consent PDF right after intake creation, filed under the same
     * IntakeDocuments/{intake folder} Drive location as manually-uploaded
     * intake documents.
     */
    private function uploadConsentPdf(Intake $intake): void
    {
        $pdfContents = $this->pdfService->consentPdfForIntake($intake);

        if ($pdfContents === null) {
            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'consent-pdf-');
        file_put_contents($tempPath, $pdfContents);

        $filename = "Consents-{$intake->reference_number}-{$intake->child_first_name}-{$intake->child_last_name}.pdf";
        $file = new UploadedFile($tempPath, $filename, 'application/pdf', null, true);

        $uploaded = $this->drive->upload($file, 'client', $this->intakeFolderName($intake));

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
