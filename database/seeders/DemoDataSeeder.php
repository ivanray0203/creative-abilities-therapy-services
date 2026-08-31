<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\BillingAccount;
use App\Models\Career;
use App\Models\Client;
use App\Models\ClientService;
use App\Models\Complaint;
use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\Invoice;
use App\Models\ScheduleSession;
use App\Models\ServiceOffering;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\IntakeSubmissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A working clinic's worth of data: therapists with real caseloads, intakes
 * at every stage of the pipeline, clients with delivered and upcoming
 * sessions, and the two hops of invoicing between them.
 *
 * Written by hand rather than left to factories — the point is data that
 * hangs together (a session's therapist is the one assigned to that service;
 * an invoice's total matches the hours behind it), so the screens can be read
 * as though the clinic really ran this way.
 *
 * Idempotent: every record is keyed on something stable, so re-running tops
 * the data back up rather than duplicating it.
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'Demo@12345';

    /** Kept to a real Alberta footprint, since funding here is FSCD-driven. */
    private const CITIES = [
        ['Edmonton', 'T5J 2R7'],
        ['Sherwood Park', 'T8A 3H8'],
        ['St. Albert', 'T8N 5C9'],
        ['Spruce Grove', 'T7X 4P4'],
        ['Leduc', 'T9E 6V5'],
        ['Calgary', 'T2P 1J9'],
    ];

    public function run(): void
    {
        $this->priceServices();

        $therapists = $this->therapists();
        $admin = User::query()->where('role', 'admin')->orderBy('id')->firstOrFail();

        $this->pipelineIntakes($therapists);
        $clients = $this->activeClients($therapists);

        foreach ($clients as $client) {
            $this->sessionsFor($client);
        }

        $this->invoices($clients, $admin);
        $this->complaints($clients);
        $this->recruitment();

        $this->command->info('Seeded '.count($clients).' clients, '.count($therapists).' therapists.');
    }

    /**
     * The offerings exist but most carry no rate, which leaves every invoice
     * and progress figure at zero.
     */
    private function priceServices(): void
    {
        $rates = [
            'Occupational Therapy' => 145,
            'Speech and Language Therapy' => 140,
            'Physiotherapy' => 135,
            'Psychological Support' => 190,
            'Counselling' => 125,
            'Behavioural Aide Services' => 65,
            'Behavioural Consulting' => 155,
            'Respite Aide Services' => 55,
            'Community Aide Services' => 60,
        ];

        foreach ($rates as $name => $rate) {
            ServiceOffering::query()->where('name', $name)->update([
                'base_price' => $rate,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<string, User> keyed by the service each one leads on
     */
    private function therapists(): array
    {
        $people = [
            'Occupational Therapy' => ['Marielle', 'Fontaine', 'Occupational Therapist', ['OT Reg. (AB)', 'MScOT'], 92.5, 18],
            'Speech and Language Therapy' => ['Priya', 'Raghunathan', 'Speech-Language Pathologist', ['R.SLP', 'MSc SLP'], 95.0, 16],
            'Physiotherapy' => ['Daniel', 'Okonkwo', 'Physiotherapist', ['MPT', 'CAFCI'], 88.0, 20],
            'Behavioural Consulting' => ['Sarah', 'Whitecalf', 'Behaviour Consultant', ['BCBA', 'MEd'], 98.0, 14],
            'Psychological Support' => ['Tomas', 'Lindqvist', 'Registered Psychologist', ['R.Psych', 'PhD'], 145.0, 10],
            'Counselling' => ['Renee', 'Beaulieu', 'Registered Counsellor', ['CCC', 'MC'], 105.0, 22],
            'Behavioural Aide Services' => ['Grace', 'Mensah', 'Behaviour Aide', ['Behaviour Aide Certificate'], 38.0, 12],
        ];

        $therapists = [];

        foreach ($people as $service => [$first, $last, $position, $credentials, $rate, $caseload]) {
            $email = Str::lower("{$first}.{$last}@cats.test");

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'role' => 'therapist',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make(self::PASSWORD),
                ],
            );

            TeamMember::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'position' => $position,
                    'department' => 'clinical_services',
                    'employment_status' => 'active',
                    'hire_date' => now()->subMonths(random_int(8, 60))->toDateString(),
                    'hourly_rate' => $rate,
                    'maximum_caseload' => $caseload,
                    'credentials' => $credentials,
                    'specializations' => [$service],
                    'phone' => $this->phone(),
                    'office_phone' => '780-555-0100',
                    'city' => 'Edmonton',
                    'province' => 'AB',
                    'emergency_contact_name' => 'Clinic Front Desk',
                    'emergency_contact_phone' => '780-555-0100',
                    'can_manage_clients' => true,
                    'can_access_finance' => false,
                    'can_manage_team' => false,
                    'availability' => $this->weekdayAvailability(),
                ],
            );

            $therapists[$service] = $user;
        }

        return $therapists;
    }

    /**
     * Intakes still working through the pipeline — one at each stage, so the
     * admin list, the review queue and the status filters all have something
     * to show.
     *
     * @param  array<string, User>  $therapists
     */
    private function pipelineIntakes(array $therapists): void
    {
        $pipeline = [
            ['Noah', 'Bergeron', 'pending', 'BDS-FSCD', ['Occupational Therapy', 'Speech and Language Therapy'], null],
            ['Amara', 'Diallo', 'under_review', 'Insurance', ['Physiotherapy'], null],
            ['Liam', 'Kowalczyk', 'waitlist', 'private', ['Behavioural Aide Services'], null],
            ['Sofia', 'Marchetti', 'pending', 'SS-FSCD', ['Speech and Language Therapy'], 'Speech and Language Therapy'],
            ['Elias', 'Nakamura', 'pending', 'Counselling-FSCD', ['Counselling', 'Psychological Support'], 'Psychological Support'],
            ['Hannah', 'Okafor', 'denied', 'private', ['Respite Aide Services'], null],
        ];

        foreach ($pipeline as $index => [$first, $last, $status, $funding, $services, $sentTo]) {
            $intake = $this->intake($first, $last, $status, $funding, $services, $index);

            // The two with a therapist named are awaiting that person's
            // decision, which is what the review queue is meant to show.
            if ($sentTo !== null && $intake->therapistReviews()->count() === 0) {
                $therapist = $this->therapistFor($therapists, $sentTo);

                foreach ($services as $service) {
                    IntakeTherapistApproval::query()->create([
                        'intake_id' => $intake->id,
                        'therapist_id' => $therapist->id,
                        'service' => $service,
                        'status' => 'pending',
                    ]);

                    IntakeTherapistApprovalHistory::query()->create([
                        'intake_id' => $intake->id,
                        'therapist_id' => $therapist->id,
                        'service' => $service,
                        'status' => 'sent',
                        'notes' => "Sent to {$therapist->full_name}",
                    ]);
                }
            }
        }
    }

    /**
     * Children already in care, each with availed services, an approval
     * history behind them and a parent login.
     *
     * @param  array<string, User>  $therapists
     * @return array<int, Client>
     */
    private function activeClients(array $therapists): array
    {
        $roster = [
            ['Mateo', 'Alvarez', 'BDS-FSCD', [
                ['Occupational Therapy', 'Weekly', 24, 'Improve pencil grasp and self-feeding independence.'],
                ['Speech and Language Therapy', 'Weekly', 20, 'Expand expressive vocabulary to 200+ words.'],
            ]],
            ['Freya', 'Lindberg', 'SS-FSCD', [
                ['Physiotherapy', 'Bi-weekly', 16, 'Independent stair climbing with alternating feet.'],
            ]],
            ['Jonah', 'Whitehorse', 'Insurance', [
                ['Behavioural Consulting', 'Weekly', 30, 'Reduce transition-related outbursts at school.'],
                ['Behavioural Aide Services', 'Weekly', 40, 'Support the classroom behaviour plan.'],
            ]],
            ['Isla', 'Fitzgerald', 'private', [
                ['Speech and Language Therapy', 'Weekly', 12, 'Clear /r/ and /s/ articulation in conversation.'],
            ]],
            ['Kai', 'Nguyen', 'Counselling-FSCD', [
                ['Psychological Support', 'Monthly', 8, 'Anxiety management strategies for school refusal.'],
                ['Counselling', 'Bi-weekly', 12, 'Build coping skills for family transitions.'],
            ]],
        ];

        $clients = [];

        foreach ($roster as $index => [$first, $last, $funding, $services]) {
            $intake = $this->intake(
                $first,
                $last,
                'approved',
                $funding,
                array_column($services, 0),
                $index + 100,
                approved: true,
            );

            $parentEmail = $intake->primary_parent_email;

            $parent = User::query()->updateOrCreate(
                ['email' => $parentEmail],
                [
                    'first_name' => Str::before((string) $intake->primary_parent_name, ' '),
                    'last_name' => Str::afterLast((string) $intake->primary_parent_name, ' '),
                    'role' => 'client',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make(self::PASSWORD),
                ],
            );

            $lead = $this->therapistFor($therapists, $services[0][0]);

            $client = Client::query()->firstOrCreate(
                ['original_intake_id' => $intake->id],
                [
                    'user_id' => $parent->id,
                    'primary_therapist_id' => $lead->id,
                    'assigned_therapist_id' => $lead->id,
                    'assigned_at' => now()->subMonths(4)->toDateString(),
                    'approved_date' => now()->subMonths(4),
                    'status' => 'active',
                    'contract_start_date' => now()->subMonths(4)->startOfMonth()->toDateString(),
                    'contract_end_date' => now()->addMonths(8)->endOfMonth()->toDateString(),
                    'signed_date' => now()->subMonths(4)->toDateString(),
                    'clinical_notes' => [
                        [
                            'id' => (string) Str::uuid(),
                            'user' => 'Intake Coordinator',
                            'date' => now()->subMonths(4)->toDateString(),
                            'time' => '9:15 AM',
                            'note' => 'Service agreement signed. Family prefers after-school appointments.',
                        ],
                    ],
                ],
            );

            BillingAccount::query()->firstOrCreate(
                ['client_id' => $client->id],
                ['currency' => 'CAD', 'balance' => 0],
            );

            $intake->forceFill(['linked_client_id' => $client->id])->save();

            foreach ($services as [$serviceName, $frequency, $authorised, $goals]) {
                $therapist = $this->therapistFor($therapists, $serviceName);
                $offering = ServiceOffering::query()->where('name', $serviceName)->first();

                if ($offering === null) {
                    continue;
                }

                ClientService::query()->firstOrCreate(
                    [
                        'client_id' => $client->id,
                        'service_id' => $offering->id,
                        'therapist_id' => $therapist->id,
                    ],
                    [
                        'frequency' => $frequency,
                        'duration' => '60 minutes',
                        'start_date' => now()->subMonths(4)->toDateString(),
                        'funding_source' => $funding,
                        'no_sessions' => $authorised,
                        'goals' => $goals,
                    ],
                );

                $client->careTeam()->syncWithoutDetaching([$therapist->id]);

                $this->approvalTrail($intake, $therapist, $serviceName);
            }

            $client->refreshServiceAvailedCache();
            $clients[] = $client->fresh();
        }

        return $clients;
    }

    /**
     * Sessions running from four months back to a fortnight ahead: delivered
     * ones carry the clock-out time and a note, and each service keeps a
     * couple of unbooked slots so it still appears in the session picker.
     */
    private function sessionsFor(Client $client): void
    {
        $client->loadMissing('clientServices.service');

        foreach ($client->clientServices as $clientService) {
            if ($clientService->sessions()->exists()) {
                continue;
            }

            $offering = $clientService->service !== null ? $clientService->service->name : 'Session';

            // Six past visits, one missed, one cancelled, two upcoming.
            $plan = [
                ...array_fill(0, 6, 'completed'),
                'no_show',
                'cancelled',
                'scheduled',
                'scheduled',
            ];

            foreach ($plan as $index => $status) {
                $isUpcoming = $status === 'scheduled';
                $start = $isUpcoming
                    ? now()->addDays(($index - 8) * 7 + 3)->setTime(15, 30)
                    : now()->subWeeks(12 - $index)->setTime(random_int(9, 16), [0, 30][random_int(0, 1)]);

                $delivered = $status === 'completed';

                $session = ScheduleSession::query()->create([
                    'client_id' => $client->id,
                    'therapist_id' => $clientService->therapist_id,
                    'service_id' => $clientService->service_id,
                    'scheduled_start' => $start,
                    'scheduled_end' => (clone $start)->addMinutes(60),
                    'location' => ['Clinic — Room 2', 'Clinic — Gym', 'Home Visit', 'School'][random_int(0, 3)],
                    'duration' => 60,
                    'status' => $delivered ? 'confirmed' : $status,
                    'start_time' => $delivered ? $start : null,
                    'end_time' => $delivered ? (clone $start)->addMinutes(random_int(50, 65)) : null,
                    'elapsed_time' => $delivered ? sprintf('00:%02d:00', random_int(50, 59)) : null,
                    'notes' => $delivered ? $this->sessionNote($offering) : null,
                    'cancel_reason' => $status === 'cancelled' ? 'Family unwell — rebooked.' : null,
                ]);

                $session->clientServices()->attach($clientService->id);
            }
        }
    }

    /**
     * Both hops: the therapist bills the clinic for delivered work, and the
     * clinic bills the family for the same visits.
     *
     * @param  array<int, Client>  $clients
     */
    private function invoices(array $clients, User $admin): void
    {
        foreach ($clients as $index => $client) {
            $client->loadMissing(['clientServices.service', 'clientServices.therapist', 'originalIntake']);

            foreach ($client->clientServices as $position => $clientService) {
                $delivered = $clientService->sessions()
                    ->whereIn('status', ['pending', 'confirmed', 'completed'])
                    ->count();

                if ($delivered === 0 || $clientService->therapist === null) {
                    continue;
                }

                $rate = $clientService->service !== null ? (float) $clientService->service->base_price : 120.0;
                $reference = "INV-DEMO-{$client->id}-{$clientService->id}";

                if (Invoice::query()->where('invoice_id', $reference.'-T')->exists()) {
                    continue;
                }

                $therapistInvoice = $this->invoice(
                    $reference.'-T',
                    $client,
                    $clientService->therapist_id,
                    'therapist',
                    $clientService->serviceName(),
                    $delivered,
                    // The clinic keeps a margin, so the therapist bills less
                    // than the family is charged.
                    round($rate * 0.7, 2),
                    ['paid', 'sent', 'paid'][$index % 3],
                    $admin,
                );

                $this->invoice(
                    $reference.'-C',
                    $client,
                    $clientService->therapist_id,
                    'admin',
                    $clientService->serviceName(),
                    $delivered,
                    $rate,
                    ['paid', 'sent', 'overdue', 'draft'][($index + $position) % 4],
                    $admin,
                    $therapistInvoice->id,
                );
            }
        }
    }

    private function invoice(
        string $reference,
        Client $client,
        ?int $therapistId,
        string $billedBy,
        string $serviceName,
        int $sessions,
        float $rate,
        string $status,
        User $admin,
        ?int $linkedTherapistInvoiceId = null,
    ): Invoice {
        $intake = $client->originalIntake;
        $issuedAt = now()->subDays(random_int(5, 40));

        $invoice = new Invoice([
            'client_id' => $client->id,
            'therapist_id' => $therapistId,
            'billing_account_id' => $client->billing?->id,
            'billed_by' => $billedBy,
            'invoice_id' => $reference,
            'invoice_date' => $issuedAt->toDateString(),
            'due_date' => $issuedAt->copy()->addDays(30)->toDateString(),
            'tax_percentage' => 5.00,
            'status' => $status,
            'issued_by_id' => $admin->id,
            'linked_therapist_invoice_id' => $linkedTherapistInvoiceId,
            'bill_to_name' => $billedBy === 'therapist' ? config('cats.name') : $intake?->primary_parent_name,
            'bill_to_email' => $billedBy === 'therapist' ? config('cats.email') : $intake?->primary_parent_email,
            'services' => [[
                'name' => $serviceName,
                'description' => $billedBy === 'therapist' ? 'Sessions delivered' : 'Therapy sessions',
                'numberOfSessions' => $sessions,
                'rate' => number_format($rate, 2),
                'rate_numeric' => $rate,
            ]],
            'timeline' => [[
                'id' => (string) Str::uuid(),
                'title' => 'Invoice created',
                'date' => $issuedAt->toDateString(),
                'time' => $issuedAt->format('g:i:s A'),
            ]],
        ]);

        $invoice->calculateTotals();

        if ($status === 'paid') {
            $invoice->paid_at = $issuedAt->copy()->addDays(12);
            $invoice->paid_date = $issuedAt->copy()->addDays(12);
            $invoice->amount_due = 0;
        }

        $invoice->save();

        return $invoice;
    }

    /**
     * @param  array<int, Client>  $clients
     */
    private function complaints(array $clients): void
    {
        if ($clients === [] || Complaint::query()->where('subject', 'like', 'Demo:%')->exists()) {
            return;
        }

        $client = $clients[0];
        $session = $client->sessions()->where('status', 'confirmed')->first();

        Complaint::query()->create([
            'client_id' => $client->id,
            'therapist_id' => $client->primary_therapist_id,
            'session_id' => $session?->id,
            'type' => 'complaints',
            'category' => 'scheduling',
            'status' => 'open',
            'complained_by' => 'client',
            'subject' => 'Demo: repeated last-minute reschedules',
            'description' => 'Our last two appointments were moved with under an hour\'s notice, which is difficult around school pickup.',
        ]);

        Complaint::query()->create([
            'client_id' => $clients[1]->id,
            'therapist_id' => $clients[1]->primary_therapist_id,
            'type' => 'complaints',
            'category' => 'invoice',
            'status' => 'resolved',
            'complained_by' => 'client',
            'subject' => 'Demo: invoice charged the wrong funding source',
            'description' => 'The March invoice was billed privately although FSCD approval was already in place.',
            'admin_response' => 'Reissued against FSCD and credited the difference.',
            'resolve_at' => now()->subDays(6),
        ]);
    }

    /**
     * Open positions with a few applicants at different stages.
     */
    private function recruitment(): void
    {
        $positions = [
            ['Speech-Language Pathologist', 'Edmonton, AB', 'Full-time', '$95,000 - $110,000 / year'],
            ['Behaviour Aide', 'Sherwood Park, AB', 'Part-time', '$26 - $32 / hour'],
            ['Occupational Therapist (Casual)', 'St. Albert, AB', 'Casual', '$60 - $75 / hour'],
        ];

        $careers = [];

        foreach ($positions as [$position, $location, $schedule, $rate]) {
            $careers[] = Career::query()->updateOrCreate(
                ['position' => $position],
                [
                    'location' => $location,
                    'schedule' => $schedule,
                    'contract' => $schedule,
                    'rate' => $rate,
                    'level' => 'Intermediate',
                    'hours' => $schedule === 'Full-time' ? '37.5 hrs / week' : 'Flexible',
                    'short_description' => "Join a paediatric team supporting children across the Edmonton region as a {$position}.",
                    'about_description' => 'You will carry your own caseload, work alongside a multidisciplinary team, and contribute to FSCD-funded programming.',
                    'responsibilities' => [
                        'Assess and treat paediatric clients',
                        'Write funder-ready reports',
                        'Collaborate with families and schools',
                    ],
                    'qualifications' => [
                        'Registration with the relevant Alberta college',
                        'Clear criminal record and vulnerable sector check',
                        "Valid driver's licence",
                    ],
                    'is_active' => true,
                    'due_date' => now()->addWeeks(6)->toDateString(),
                    'required_documents' => ['Resume', 'Cover Letter', 'Registration Certificate'],
                ],
            );
        }

        $applicants = [
            ['Rowena', 'Castillo', 0, 'reviewing', 'Registered SLP relocating from Winnipeg.'],
            ['Devon', 'Mbeki', 1, 'interview_scheduled', 'Two years of classroom aide experience.'],
            ['Aiko', 'Tanabe', 0, 'pending', 'New graduate, completed placement in paediatrics.'],
            ['Callum', 'Reyes', 2, 'declined', 'Not yet registered in Alberta.'],
        ];

        foreach ($applicants as [$first, $last, $careerIndex, $status, $reason]) {
            $career = $careers[$careerIndex];

            Application::query()->updateOrCreate(
                ['email' => Str::lower("{$first}.{$last}@example.com")],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => $this->phone(),
                    'city' => self::CITIES[array_rand(self::CITIES)][0],
                    'province' => 'AB',
                    'position_applied' => $career->position,
                    'position_id' => $career->id,
                    'profession_status' => 'Registered',
                    'preferred_start_date' => now()->addWeeks(4)->toDateString(),
                    'application_status' => $status,
                    'reason_for_applying' => $reason,
                    'lead_source' => ['Indeed', 'Referral', 'Website'][random_int(0, 2)],
                    'has_vehicle' => true,
                    'drivers_license' => true,
                    'declined' => $status === 'declined',
                ],
            );
        }
    }

    /**
     * The clinician who leads on a service. Aide and community work sits with
     * the behaviour lead when no one is named for it specifically.
     *
     * @param  array<string, User>  $therapists
     */
    private function therapistFor(array $therapists, string $service): User
    {
        if (isset($therapists[$service])) {
            return $therapists[$service];
        }

        // `therapists()` always seeds the behaviour lead, so this is a real
        // fallback rather than a guess at an empty roster.
        return $therapists['Behavioural Consulting'];
    }

    /**
     * @param  array<int, string>  $services
     */
    private function intake(
        string $first,
        string $last,
        string $status,
        string $funding,
        array $services,
        int $seed,
        bool $approved = false,
    ): Intake {
        [$city, $postal] = self::CITIES[$seed % count(self::CITIES)];
        $parentFirst = ['Marisol', 'Grace', 'Tobias', 'Nadia', 'Peter', 'Lucia', 'Arun', 'Beth'][$seed % 8];
        $dob = now()->subYears(random_int(3, 11))->subDays(random_int(0, 300));

        // Derived so the grid and the flat day/time lists stay consistent,
        // exactly as a real submission does.
        $availability = IntakeSubmissionService::resolveAvailability([
            'Monday' => ['Afternoons (12pm-3pm)'],
            'Wednesday' => ['Afternoons (12pm-3pm)', 'Evenings (4pm-7pm)'],
            'Thursday' => ['Evenings (4pm-7pm)'],
        ]);

        return Intake::query()->firstOrCreate(
            ['child_first_name' => $first, 'child_last_name' => $last],
            [
                'date_of_birth' => $dob->toDateString(),
                'age' => (int) $dob->diffInYears(now()),
                'gender' => $seed % 2 === 0 ? 'male' : 'female',
                'status' => $status,
                'approved_as_client' => $approved,
                'reviewed' => $approved,
                'street_address' => (100 + $seed * 7).' '.['Whyte Ave', 'Jasper Ave', 'Wye Rd', 'St Albert Trail', 'Grove Dr'][$seed % 5],
                'city' => $city,
                'state_province' => 'Alberta',
                'postal_code' => $postal,
                'grade_level' => ['Preschool', 'Kindergarten', 'Grade 1', 'Grade 3', 'Grade 5'][$seed % 5],
                'school_name' => ['Rutherford School', 'Brander Gardens', 'Lymburn Elementary', 'Homeschooled'][$seed % 4],
                'services_needed' => $services,
                'diagnosis' => [['Autism Spectrum Disorder'], ['Global Developmental Delay'], ['ADHD'], ['Speech Delay'], ['Cerebral Palsy']][$seed % 5],
                'medical_conditions' => $seed % 3 === 0 ? 'Mild asthma — inhaler kept in school bag.' : null,
                'has_medical_conditions' => $seed % 3 === 0,
                'languages_spoken_at_home' => ['English', 'English, Tagalog', 'English, Punjabi', 'English, French'][$seed % 4],
                'currently_receiving_services' => $seed % 4 === 0,
                'funding_source' => $funding,
                'funding_source_info' => $this->fundingInfo($funding),
                'available_days' => $availability['days'],
                'preferred_times' => $availability['times'],
                'availability_slots' => $availability['slots'],
                'primary_parent_name' => "{$parentFirst} {$last}",
                'primary_parent_phone' => $this->phone(),
                'primary_parent_email' => Str::lower("{$parentFirst}.{$last}@example.com"),
                'primary_relationship_to_child' => $seed % 5 === 0 ? 'guardian' : 'Parent',
                'primary_contact_method' => 'email',
                'emergency_contact_name' => ['Ruth Alvarez', 'Kenji Mori', 'Dana Fitz', 'Omar Haddad'][$seed % 4],
                'emergency_contact_phone' => $this->phone(),
                'emergency_contact_relationship' => ['grandparent', 'aunt', 'guardian', 'uncle'][$seed % 4],
                'referral_source' => ['Family Doctor', 'Community Event', 'School Referral', 'Word of Mouth'][$seed % 4],
                'reference_number' => 'INT-'.now()->year.'-'.str_pad((string) (500 + $seed), 3, '0', STR_PAD_LEFT),
                'timeline' => [[
                    'id' => (string) Str::uuid(),
                    'title' => 'Intake form submitted via website',
                    'date' => now()->subMonths($approved ? 5 : 1)->toDateString(),
                    'time' => '10:02:00 AM',
                ]],
                'created_at' => now()->subMonths($approved ? 5 : 1),
            ],
        );
    }

    private function approvalTrail(Intake $intake, User $therapist, string $service): void
    {
        $review = $intake->therapistReviews()->where('service', $service)->first();

        if ($review !== null) {
            return;
        }

        $decidedAt = Carbon::parse($intake->created_at)->addDays(3);

        IntakeTherapistApproval::query()->create([
            'intake_id' => $intake->id,
            'therapist_id' => $therapist->id,
            'service' => $service,
            'status' => 'approved',
            'decided_at' => $decidedAt,
        ]);

        foreach (['sent', 'approved'] as $step) {
            IntakeTherapistApprovalHistory::query()->create([
                'intake_id' => $intake->id,
                'therapist_id' => $therapist->id,
                'service' => $service,
                'status' => $step,
                'decided_at' => $step === 'approved' ? $decidedAt : null,
                'created_at' => $step === 'sent' ? $decidedAt->copy()->subDays(2) : $decidedAt,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fundingInfo(string $funding): array
    {
        if ($funding === 'Insurance') {
            return [
                'insurance_provider' => 'Alberta Blue Cross',
                'policy_number' => 'ABC-'.random_int(100000, 999999),
                'certificate_number' => (string) random_int(10000, 99999),
                'policy_holder_name' => 'Parent on file',
                'consents' => [],
            ];
        }

        if ($funding === 'private') {
            return ['consents' => []];
        }

        return [
            'FSCD_case_worker_name' => ['Janine Petrov', 'Cory Ahenakew', 'Melanie Dube'][random_int(0, 2)],
            'FSCD_case_worker_email' => 'fscd.worker@gov.ab.example',
            'FSCD_approval_start_date' => now()->subMonths(5)->toDateString(),
            'FSCD_approval_end_date' => now()->addMonths(7)->toDateString(),
            'consents' => [
                ['title' => 'FSCD Worker Communication', 'datetime' => now()->subMonths(5)->toIso8601String()],
                ['title' => 'Reports Sharing', 'datetime' => now()->subMonths(5)->toIso8601String()],
            ],
        ];
    }

    private function sessionNote(string $service): string
    {
        $notes = [
            "Worked on the current {$service} goals; good engagement throughout.",
            'Parent joined the last ten minutes for home-programme handover.',
            'Needed more prompting than last week — shorter activities worked better.',
            'Strong session. Met the target three times independently.',
            'Reviewed school strategies with the aide; consistency improving.',
        ];

        return $notes[random_int(0, count($notes) - 1)];
    }

    private function phone(): string
    {
        return '780-'.random_int(200, 899).'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function weekdayAvailability(): array
    {
        return array_map(fn (string $day): array => [
            'week_day' => $day,
            'time_from' => '09:00',
            'time_to' => '17:00',
        ], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
    }
}
