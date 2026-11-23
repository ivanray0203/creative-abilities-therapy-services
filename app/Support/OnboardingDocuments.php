<?php

namespace App\Support;

/**
 * The documents a new hire uploads during onboarding, matching the clinic's
 * onboarding form.
 *
 * Every role hands in the same set, except that the professional license
 * and liability insurance are for clinicians only — aides don't hold either.
 * A position can ask for more on top (Admin → Positions, "required
 * documents"), and those are added after the standard ones.
 *
 * This is the one list behind the onboarding email, the missing-document
 * checks that gate a hire, and the document types offered at upload, so the
 * names must stay exactly as they are here: an upload counts towards a
 * requirement only when its type matches the name.
 */
class OnboardingDocuments
{
    public const DRIVERS_LICENSE = "Driver's License";

    public const CRIMINAL_RECORD_CHECK = 'Criminal Record / Vulnerable Sector Check';

    public const VOID_CHEQUE = 'Void Cheque / Direct Deposit Information';

    public const PROFESSIONAL_LICENSE = 'Professional License and Certification';

    public const LIABILITY_INSURANCE = 'Professional Liability Insurance';

    /** Required from every role, aides included. */
    public const FOR_EVERYONE = [
        self::DRIVERS_LICENSE,
        self::CRIMINAL_RECORD_CHECK,
        self::VOID_CHEQUE,
    ];

    /** Required from clinicians only. */
    public const CLINICIAN_ONLY = [
        self::PROFESSIONAL_LICENSE,
        self::LIABILITY_INSURANCE,
    ];

    /**
     * Whether a position title is an aide's: it ends in "Aide" ("Behavioural
     * & Developmental Aide", "Community & Respite Aide"). The same rule
     * decides the aide portal — see TeamMember::isAide().
     */
    public static function isAidePosition(?string $position): bool
    {
        return str_ends_with(strtolower(trim((string) $position)), 'aide');
    }

    /**
     * The standard documents for the position, followed by the position's
     * own extra ones, without repeats.
     *
     * @param  array<int, string>  $positionDocuments
     * @return array<int, string>
     */
    public static function forPosition(?string $position, array $positionDocuments = []): array
    {
        $standard = self::isAidePosition($position)
            ? self::FOR_EVERYONE
            : [...self::FOR_EVERYONE, ...self::CLINICIAN_ONLY];

        return array_values(array_unique([...$standard, ...$positionDocuments]));
    }
}
