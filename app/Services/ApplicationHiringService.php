<?php

namespace App\Services;

use App\Jobs\CreateMailcowMailbox;
use App\Models\Application;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Promotes a hired job application into a therapist User + TeamMember.
 *
 * Ported 1:1 from cats-backend/cats/serializers.py
 * ApplicationSerializer._create_user_and_team_member().
 */
class ApplicationHiringService
{
    /**
     * @return array{user: User, teamMember: TeamMember, rawPassword: ?string}
     */
    public function hire(Application $application, float $hourlyRate): array
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
                'employment_status' => 'active',
                'hourly_rate' => $hourlyRate,
                'application_id' => $application->id,
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

            $teamMember->save();

            if ($rawPassword !== null && filled(config('services.mailcow.api_key'))) {
                CreateMailcowMailbox::dispatch(
                    $teamMember->id,
                    $application->first_name,
                    $application->last_name,
                    $rawPassword,
                )->afterCommit();
            }

            // Offer letter / login-credentials emails are sent by the
            // caller (ApplicationController) once this transaction commits.

            return ['user' => $user, 'teamMember' => $teamMember, 'rawPassword' => $rawPassword];
        });
    }
}
