<?php

namespace App\Services;

use App\Jobs\CreateMailcowMailbox;
use App\Models\Application;
use App\Models\ClientDocument;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Turns a job application into a therapist User + TeamMember in two steps.
 *
 * `startOnboarding()` runs when the candidate has signed their offer: it
 * creates the portal account and a team member held at the `onboarding`
 * employment status, which is what keeps every menu but Profile hidden
 * while they upload their documents. `hire()` runs once an admin has
 * reviewed those documents and flips the team member to active.
 *
 * Account/team-member shape ported from cats-backend/cats/serializers.py
 * ApplicationSerializer._create_user_and_team_member().
 */
class ApplicationHiringService
{
    /**
     * @return array{user: User, teamMember: TeamMember, rawPassword: ?string}
     */
    public function startOnboarding(Application $application, float $hourlyRate): array
    {
        if ($application->hired) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been hired.',
            ]);
        }

        return DB::transaction(function () use ($application, $hourlyRate): array {
            $email = Str::lower(trim($application->email));

            $user = User::query()->where('email', $email)->first();
            $rawPassword = null;

            if (! $user) {
                $rawPassword = Str::random(10);

                $user = User::query()->create([
                    'email' => $email,
                    'password' => $rawPassword,
                    'role' => 'therapist',
                    'first_name' => $application->first_name,
                    'last_name' => $application->last_name,
                    'phone' => $application->phone,
                    'is_active' => true,
                ]);
            }

            $teamMember = TeamMember::query()->firstOrNew(['user_id' => $user->id]);

            $defaults = [
                'position' => $application->position_applied,
                'hire_date' => $application->preferred_start_date ?? now()->toDateString(),
                'department' => 'clinical_services',
                'hourly_rate' => $hourlyRate,
                'phone' => $application->phone,
                'street_address' => $application->street_address,
                'address_line_2' => $application->address_line_2,
                'city' => $application->city,
                'province' => $application->province,
                'zip_code' => $application->zip_code,
                'availability' => $application->availability,
                'resident_status' => $application->resident_status,
                'secondary_email' => $application->email,
            ];

            foreach ($defaults as $field => $value) {
                if ($teamMember->{$field} === null) {
                    $teamMember->setAttribute($field, $value);
                }
            }

            // A brand-new record's status is set explicitly rather than
            // through the null-only fill above: the column has a DB default
            // of `active`, and an existing member re-hired through the
            // pipeline must go back through onboarding too.
            $teamMember->employment_status = 'onboarding';
            $teamMember->application()->associate($application);
            $teamMember->save();

            if ($rawPassword !== null && filled(config('services.mailcow.api_key'))) {
                CreateMailcowMailbox::dispatch(
                    $teamMember->id,
                    $application->first_name,
                    $application->last_name,
                    $rawPassword,
                )->afterCommit();
            }

            // The login-credentials email is sent by the caller
            // (ApplicationController) once this transaction commits.

            return ['user' => $user, 'teamMember' => $teamMember, 'rawPassword' => $rawPassword];
        });
    }

    /**
     * The documents the position requires that the candidate has not
     * uploaded yet. Empty once onboarding is complete.
     *
     * @return array<int, string>
     */
    public function missingDocuments(Application $application): array
    {
        $teamMember = $this->teamMemberFor($application);

        if ($teamMember === null) {
            return $application->requiredDocuments();
        }

        $uploaded = ClientDocument::query()
            ->where('user_id', $teamMember->user_id)
            ->pluck('doc_type')
            ->all();

        return array_values(array_diff($application->requiredDocuments(), $uploaded));
    }

    /**
     * Activates the team member created at onboarding. The rate was agreed
     * on the signed offer, so nothing is asked for here.
     */
    public function hire(Application $application): TeamMember
    {
        if ($application->hired) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been hired.',
            ]);
        }

        $teamMember = $this->teamMemberFor($application);

        if ($teamMember === null) {
            throw ValidationException::withMessages([
                'application_status' => 'This candidate has not started onboarding yet.',
            ]);
        }

        $teamMember->update(['employment_status' => 'active']);

        return $teamMember;
    }

    public function teamMemberFor(Application $application): ?TeamMember
    {
        return TeamMember::query()
            ->where('application_id', $application->id)
            ->latest('id')
            ->first();
    }
}
