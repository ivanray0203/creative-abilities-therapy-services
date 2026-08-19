<?php

namespace Database\Seeders;

use App\Models\InvoiceService;
use Illuminate\Database\Seeder;

/**
 * The invoice rate card: every "Service Provided" line an invoice can bill,
 * with its FSCD and private rate, transcribed from the clinic's Services
 * rate sheet (Invoice/Timesheet). A null rate is a blank column on the
 * sheet — that line is not billable under that funding stream.
 *
 * Keyed on `code`, so re-running updates rates in place rather than
 * duplicating lines. `sort_order` preserves the sheet's own ordering, which
 * groups the lines by discipline.
 */
class InvoiceServiceSeeder extends Seeder
{
    /**
     * The rate sheet itself: [name, code, discipline, FSCD rate, private rate].
     *
     * Public because InvoiceServiceFactory builds its models from these same
     * rows — tests bill against real rate lines, never invented ones.
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: string|null, 4: string|null}>
     */
    public const RATE_CARD = [
        ['Speech-Language Pathologist Home Visit', 'speech-language-pathologist-home-visit', 'slp', '100.68', '100.68'],
        ['Speech-Language Pathologist Mileage', 'speech-language-pathologist-mileage', 'slp', '0.50', null],
        ['Speech-Language Pathologist Travel', 'speech-language-pathologist-travel', 'slp', '50.34', null],
        ['Speech-Language Pathologist Summary', 'speech-language-pathologist-summary', 'slp', '100.68', '100.68'],
        ['SLP - BDS Planning', 'slp-bds-planning', 'slp', '100.68', '100.68'],
        ['SLP - Client Preparation', 'slp-client-preparation', 'slp', '100.68', '100.68'],
        ['SLP - Community Outing', 'slp-community-outing', 'slp', '100.68', '100.68'],
        ['SLP - Consult with Parent', 'slp-consult-with-parent', 'slp', '100.68', '100.68'],
        ['SLP - Documentation', 'slp-documentation', 'slp', '100.68', '100.68'],
        ['SLP - ISPP Documentation', 'slp-ispp-documentation', 'slp', '100.68', '100.68'],
        ['SLP - MDT Documentation', 'slp-mdt-documentation', 'slp', '100.68', '100.68'],
        ['SLP - Phone Consult', 'slp-phone-consult', 'slp', '100.68', '100.68'],
        ['SLP - Program Development', 'slp-program-development', 'slp', '100.68', '100.68'],
        ['SLP - Review Meeting', 'slp-review-meeting', 'slp', '100.68', '100.68'],
        ['SLP - Review Meeting and Updates', 'slp-review-meeting-and-updates', 'slp', '100.68', '100.68'],
        ['SLP - Review Updates', 'slp-review-updates', 'slp', '100.68', '100.68'],
        ['SLP - School Meeting', 'slp-school-meeting', 'slp', '100.68', '100.68'],
        ['SLP - SPPP Documentation', 'slp-sppp-documentation', 'slp', '100.68', '100.68'],
        ['SLP - Team Consult', 'slp-team-consult', 'slp', '100.68', '100.68'],
        ['SLP - Team Meeting', 'slp-team-meeting', 'slp', '100.68', '100.68'],
        ['Psychologist Home Visit', 'psychologist-home-visit', 'psych', '98.37', '98.37'],
        ['Psychologist Mileage', 'psychologist-mileage', 'psych', '0.50', null],
        ['Psychologist Travel', 'psychologist-travel', 'psych', '49.19', null],
        ['Psychologist Summary', 'psychologist-summary', 'psych', '98.37', '98.37'],
        ['Psych - BDS Planning', 'psych-bds-planning', 'psych', '98.37', '98.37'],
        ['Psych - Client Preparation', 'psych-client-preparation', 'psych', '98.37', '98.37'],
        ['Psych - Community Outing', 'psych-community-outing', 'psych', '98.37', '98.37'],
        ['Psych - Consult with Parent', 'psych-consult-with-parent', 'psych', '98.37', '98.37'],
        ['Psych - Documentation', 'psych-documentation', 'psych', '98.37', '98.37'],
        ['Psych - ISPP Documentation', 'psych-ispp-documentation', 'psych', '98.37', '98.37'],
        ['Psych - MDT Documentation', 'psych-mdt-documentation', 'psych', '98.37', '98.37'],
        ['Psych - Phone Consult', 'psych-phone-consult', 'psych', '98.37', '98.37'],
        ['Psych - Program Development', 'psych-program-development', 'psych', '98.37', '98.37'],
        ['Psych - Review Meeting', 'psych-review-meeting', 'psych', '98.37', '98.37'],
        ['Psych - Review Meeting and Updates', 'psych-review-meeting-and-updates', 'psych', '98.37', '98.37'],
        ['Psych - Review Updates', 'psych-review-updates', 'psych', '98.37', '98.37'],
        ['Psych - School Meeting', 'psych-school-meeting', 'psych', '98.37', '98.37'],
        ['Psych - SPPP Documentation', 'psych-sppp-documentation', 'psych', '98.37', '98.37'],
        ['Psych - Team Consult', 'psych-team-consult', 'psych', '98.37', '98.37'],
        ['Psych - Team Meeting', 'psych-team-meeting', 'psych', '98.37', '98.37'],
        ['Psych - Counselling (P)', 'psych-counselling-p', 'psych', '98.37', '150.00'],
        ['Psych - Counselling (F)', 'psych-counselling-f', 'psych', '98.37', '98.37'],
        ['Occupational Therapist Home Visit', 'occupational-therapist-home-visit', 'ot', '94.76', '142.14'],
        ['Occupational Therapist Mileage', 'occupational-therapist-mileage', 'ot', '0.50', null],
        ['Occupational Therapist Travel', 'occupational-therapist-travel', 'ot', '47.38', null],
        ['Occupational Therapist Summary', 'occupational-therapist-summary', 'ot', '94.76', '142.14'],
        ['OT - BDS Planning', 'ot-bds-planning', 'ot', '94.76', '142.14'],
        ['OT - Client Preparation', 'ot-client-preparation', 'ot', '94.76', '142.14'],
        ['OT - Community Outing', 'ot-community-outing', 'ot', '94.76', '142.14'],
        ['OT - Consult with Parent', 'ot-consult-with-parent', 'ot', '94.76', '142.14'],
        ['OT - Documentation', 'ot-documentation', 'ot', '94.76', '142.14'],
        ['OT - ISPP Documentation', 'ot-ispp-documentation', 'ot', '94.76', '142.14'],
        ['OT - MDT Documentation', 'ot-mdt-documentation', 'ot', '94.76', '142.14'],
        ['OT - Phone Consult', 'ot-phone-consult', 'ot', '94.76', '142.14'],
        ['OT - Program Development', 'ot-program-development', 'ot', '94.76', '142.14'],
        ['OT - Review Meeting', 'ot-review-meeting', 'ot', '94.76', '142.14'],
        ['OT - Review Meeting and Updates', 'ot-review-meeting-and-updates', 'ot', '94.76', '142.14'],
        ['OT - Review Updates', 'ot-review-updates', 'ot', '94.76', '142.14'],
        ['OT - School Meeting', 'ot-school-meeting', 'ot', '94.76', '142.14'],
        ['OT - SPPP Documentation', 'ot-sppp-documentation', 'ot', '94.76', '142.14'],
        ['OT - Team Consult', 'ot-team-consult', 'ot', '94.76', '142.14'],
        ['OT - Team Meeting', 'ot-team-meeting', 'ot', '94.76', '142.14'],
        ['Physiotherapist Home Visit', 'physiotherapist-home-visit', 'pt', '82.66', '150.00'],
        ['Physiotherapist Mileage', 'physiotherapist-mileage', 'pt', '0.50', null],
        ['Physiotherapist Travel', 'physiotherapist-travel', 'pt', '41.33', null],
        ['Physiotherapist Summary', 'physiotherapist-summary', 'pt', '82.66', '150.00'],
        ['PT - BDS Planning', 'pt-bds-planning', 'pt', '82.66', '150.00'],
        ['PT - Client Preparation', 'pt-client-preparation', 'pt', '82.66', '150.00'],
        ['PT - Community Outing', 'pt-community-outing', 'pt', '82.66', '150.00'],
        ['PT - Consult with Parent', 'pt-consult-with-parent', 'pt', '82.66', '150.00'],
        ['PT - Documentation', 'pt-documentation', 'pt', '82.66', '150.00'],
        ['PT - ISPP Documentation', 'pt-ispp-documentation', 'pt', '82.66', '150.00'],
        ['PT - MDT Documentation', 'pt-mdt-documentation', 'pt', '82.66', '150.00'],
        ['PT - Phone Consult', 'pt-phone-consult', 'pt', '82.66', '150.00'],
        ['PT - Program Development', 'pt-program-development', 'pt', '82.66', '150.00'],
        ['PT - Review Meeting', 'pt-review-meeting', 'pt', '82.66', '150.00'],
        ['PT - Review Meeting and Updates', 'pt-review-meeting-and-updates', 'pt', '82.66', '150.00'],
        ['PT - Review Updates', 'pt-review-updates', 'pt', '82.66', '150.00'],
        ['PT - School Meeting', 'pt-school-meeting', 'pt', '82.66', '150.00'],
        ['PT - SPPP Documentation', 'pt-sppp-documentation', 'pt', '82.66', '150.00'],
        ['PT - Team Consult', 'pt-team-consult', 'pt', '82.66', '150.00'],
        ['PT - Team Meeting', 'pt-team-meeting', 'pt', '82.66', '150.00'],
        ['Behavioural Consultant Home Visit', 'behavioural-consultant-home-visit', 'bc', '77.25', '115.88'],
        ['Behavioural Consultant Mileage', 'behavioural-consultant-mileage', 'bc', '0.50', null],
        ['Behavioural Consultant Travel', 'behavioural-consultant-travel', 'bc', '38.63', null],
        ['Behavioural Consultant Summary', 'behavioural-consultant-summary', 'bc', '77.25', '115.88'],
        ['BC - BDS Planning', 'bc-bds-planning', 'bc', '77.25', '115.88'],
        ['BC - Client Preparation', 'bc-client-preparation', 'bc', '77.25', '115.88'],
        ['BC - Community Outing', 'bc-community-outing', 'bc', '77.25', '115.88'],
        ['BC - Consult with Parent', 'bc-consult-with-parent', 'bc', '77.25', '115.88'],
        ['BC - Documentation', 'bc-documentation', 'bc', '77.25', '115.88'],
        ['BC - ISPP Documentation', 'bc-ispp-documentation', 'bc', '77.25', '115.88'],
        ['BC - MDT Documentation', 'bc-mdt-documentation', 'bc', '77.25', '115.88'],
        ['BC - Phone Consult', 'bc-phone-consult', 'bc', '77.25', '115.88'],
        ['BC - Program Development', 'bc-program-development', 'bc', '77.25', '115.88'],
        ['BC - Review Meeting', 'bc-review-meeting', 'bc', '77.25', '115.88'],
        ['BC - Review Meeting and Updates', 'bc-review-meeting-and-updates', 'bc', '77.25', '115.88'],
        ['BC - Review Updates', 'bc-review-updates', 'bc', '77.25', '115.88'],
        ['BC - School Meeting', 'bc-school-meeting', 'bc', '77.25', '115.88'],
        ['BC - SPPP Documentation', 'bc-sppp-documentation', 'bc', '77.25', '115.88'],
        ['BC - Team Consult', 'bc-team-consult', 'bc', '77.25', '115.88'],
        ['BC - Team Meeting', 'bc-team-meeting', 'bc', '77.25', '115.88'],
        ['Behavioural Consultant Home Visit (N)', 'behavioural-consultant-home-visit-n', 'bc', '51.50', '77.25'],
        ['Behavioural Consultant Mileage (N)', 'behavioural-consultant-mileage-n', 'bc', '0.50', null],
        ['Behavioural Consultant Travel (N)', 'behavioural-consultant-travel-n', 'bc', '25.75', null],
        ['Behavioural Consultant Summary (N)', 'behavioural-consultant-summary-n', 'bc', '51.50', '77.25'],
        ['BC - BDS Planning (N)', 'bc-bds-planning-n', 'bc', '51.50', '77.25'],
        ['BC - Client Preparation (N)', 'bc-client-preparation-n', 'bc', '51.50', '77.25'],
        ['BC - Community Outing (N)', 'bc-community-outing-n', 'bc', '51.50', '77.25'],
        ['BC - Consult with Parent (N)', 'bc-consult-with-parent-n', 'bc', '51.50', '77.25'],
        ['BC - Documentation (N)', 'bc-documentation-n', 'bc', '51.50', '77.25'],
        ['BC - ISPP Documentation (N)', 'bc-ispp-documentation-n', 'bc', '51.50', '77.25'],
        ['BC - MDT Documentation (N)', 'bc-mdt-documentation-n', 'bc', '51.50', '77.25'],
        ['BC - Phone Consult (N)', 'bc-phone-consult-n', 'bc', '51.50', '77.25'],
        ['BC - Program Development (N)', 'bc-program-development-n', 'bc', '51.50', '77.25'],
        ['BC - Review Meeting (N)', 'bc-review-meeting-n', 'bc', '51.50', '77.25'],
        ['BC - Review Meeting and Updates (N)', 'bc-review-meeting-and-updates-n', 'bc', '51.50', '77.25'],
        ['BC - Review Updates (N)', 'bc-review-updates-n', 'bc', '51.50', '77.25'],
        ['BC - School Meeting (N)', 'bc-school-meeting-n', 'bc', '51.50', '77.25'],
        ['BC - SPPP Documentation (N)', 'bc-sppp-documentation-n', 'bc', '51.50', '77.25'],
        ['BC - Team Consult (N)', 'bc-team-consult-n', 'bc', '51.50', '77.25'],
        ['BC - Team Meeting (N)', 'bc-team-meeting-n', 'bc', '51.50', '77.25'],
        ['Behavioural Aide Services', 'behavioural-aide-services', 'aide', '28.97', '28.97'],
        ['Behavioural Aide Travel', 'behavioural-aide-travel', 'aide', '7.24', null],
        ['Community Aide Services', 'community-aide-services', 'aide', '24.00', null],
        ['Community Aide Services(K)', 'community-aide-services-k', 'aide', '21.01', null],
        ['Respite Aide Services', 'respite-aide-services', 'aide', '18.76', null],
        ['Respite Aide Services(J)', 'respite-aide-services-j', 'aide', '23.10', null],
        ['Coordination Services (OT)', 'coordination-services-ot', 'ot', '94.76', null],
        ['Coordination Services (Psych)', 'coordination-services-psych', 'psych', '98.37', '98.37'],
        ['Payroll Services', 'payroll-services', 'other', '40.00', null],
        ['Behavioural Aide Services Travel', 'behavioural-aide-services-travel', 'aide', '28.97', null],
        ['Behavioural Aide Services Mileage', 'behavioural-aide-services-mileage', 'aide', '0.50', null],
        ['Physiotherapist Sessions - Sharan Gautam (reg. # 9707)', 'physiotherapist-sessions-sharan-gautam-reg-no-9707', 'pt', null, '150.00'],
        ['Psychologist Sessions - John Marquina (reg. # P6284)', 'psychologist-sessions-john-marquina-reg-no-p6284', 'psych', null, '150.00'],
        ['OT - Communication', 'ot-communication', 'ot', '94.76', null],
        ['Occupational Therapist Travel(NA)', 'occupational-therapist-travel-na', 'ot', '23.69', null],
        ['Occupational Therapist Mileage(NA)', 'occupational-therapist-mileage-na', 'ot', '0.50', null],
        ['Speech Language Pathologist Session - Zoha Azam (reg. # 6156) Services Provided to: Yusuf Mubashir, Mubashir Athar', 'speech-language-pathologist-session-zoha-azam-reg-no-6156-services-provided-to-yusuf-mubashir-mubashir-athar', 'slp', null, '150.00'],
        ['Occupational Therapist Session - Mary Ann Lerit (reg. # 4734)', 'occupational-therapist-session-mary-ann-lerit-reg-no-4734', 'ot', null, '142.14'],
        ['OT - Short-notice Cancellations', 'ot-short-notice-cancellations', 'ot', '94.76', null],
        ['PT - Short-notice Cancellations', 'pt-short-notice-cancellations', 'pt', '82.66', null],
        ['Pscyh - Short-notice Cancellations', 'pscyh-short-notice-cancellations', 'psych', '98.37', null],
        ['BC - Short-notice cancellations', 'bc-short-notice-cancellations', 'bc', '77.25', null],
        ['SLP - Short-notice Cancellations', 'slp-short-notice-cancellations', 'slp', '100.68', null],
        ['Speech Language Pathologist Session - Zoha Azam (reg. # 6156)', 'speech-language-pathologist-session-zoha-azam-reg-no-6156', 'slp', null, '150.00'],
        ['BC - Practice Consult', 'bc-practice-consult', 'bc', '77.25', null],
    ];

    public function run(): void
    {
        foreach (self::RATE_CARD as $index => [$name, $code, $discipline, $rateFscd, $ratePrivate]) {
            InvoiceService::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'discipline' => $discipline,
                    'rate_fscd' => $rateFscd,
                    'rate_private' => $ratePrivate,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
