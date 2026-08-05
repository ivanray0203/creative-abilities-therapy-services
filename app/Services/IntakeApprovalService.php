<?php

namespace App\Services;

use App\Mail\WelcomeClientAccountMail;
use App\Models\BillingAccount;
use App\Models\Client;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Promotes an approved intake into a full Client record.
 *
 * Ported 1:1 from cats-backend/cats/services.py::promote_intake_to_client.
 */
class IntakeApprovalService
{
    /**
     * Last-name prefixes that should stay attached to the last name.
     *
     * @var array<int, string>
     */
    private const LAST_NAME_PREFIXES = [
        'de', 'de la', 'del', 'dela', 'da', 'di',
        'van', 'von', 'bin', 'al', 'ibn', 'la',
    ];

    /**
     * Create a Client (plus parent User and BillingAccount) from an intake.
     *
     * The first therapist in `$therapists` becomes the client's primary
     * therapist; any remaining therapists (from other approved services on
     * the same intake) are added to the client's care team without
     * disturbing the primary assignment.
     *
     * @param  Collection<int, User>  $therapists
     * @return array{client: Client, rawPassword: string|null}
     */
    public function promote(Intake $intake, Collection $therapists = new Collection): array
    {
        $result = DB::transaction(function () use ($intake, $therapists): array {
            $primaryTherapist = $therapists->first();

            $parentEmail = $intake->primary_parent_email !== null
                ? Str::lower(trim($intake->primary_parent_email))
                : null;
            $parentName = $intake->primary_parent_name ?? '';

            $user = null;
            $rawPassword = null;
            $newAccountCreated = false;

            if ($parentEmail !== null && $parentEmail !== '') {
                $user = User::query()->where('email', $parentEmail)->first();

                if (! $user) {
                    $rawPassword = Str::random(10);
                    [$firstName, $lastName] = $this->splitName($parentName);

                    $user = User::query()->create([
                        'email' => $parentEmail,
                        'password' => $rawPassword,
                        'role' => 'client',
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'is_active' => true,
                    ]);

                    $newAccountCreated = true;
                }
            }

            // `clients.user_id` is unique — a parent who already has a Client
            // (from an earlier intake, e.g. a sibling) must reuse that same
            // row rather than trying to insert a second one for themselves.
            $existingClient = $user ? Client::query()->where('user_id', $user->id)->first() : null;

            if ($existingClient) {
                $client = $existingClient;

                if ($primaryTherapist && ! $client->primary_therapist_id) {
                    $client->assignTherapist($primaryTherapist);
                }

                $client->careTeam()->syncWithoutDetaching($therapists->pluck('id')->all());
            } else {
                $client = Client::query()->create([
                    'original_intake_id' => $intake->id,
                    'primary_therapist_id' => $primaryTherapist?->id,
                    'user_id' => $user?->id,
                    'assigned_therapist_id' => $primaryTherapist?->id,
                    'assigned_at' => $intake->assigned_at,
                ]);

                if ($primaryTherapist) {
                    $client->assignTherapist($primaryTherapist);
                }

                foreach ($therapists->skip(1) as $therapist) {
                    $client->careTeam()->syncWithoutDetaching([$therapist->id]);
                }

                BillingAccount::query()->create(['client_id' => $client->id]);
            }

            $intake->forceFill([
                'approved_as_client' => true,
                'reviewed' => true,
                'linked_client_id' => $client->id,
            ])->save();

            return [
                'client' => $client->refresh(),
                'rawPassword' => $newAccountCreated ? $rawPassword : null,
                'parentFirstName' => optional($user)->first_name ?? '',
                'parentEmail' => optional($user)->email,
            ];
        });

        if ($result['rawPassword'] !== null && $result['parentEmail'] !== null) {
            Mail::to($result['parentEmail'])->send(new WelcomeClientAccountMail(
                $result['parentFirstName'],
                $result['parentEmail'],
                $result['rawPassword'],
            ));
        }

        return [
            'client' => $result['client'],
            'rawPassword' => $result['rawPassword'],
        ];
    }

    /**
     * Split a free-text full name into [first name, last name], keeping
     * multi-word last-name prefixes ("de la cruz", "van helsing") together.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        if (trim($fullName) === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $count = count($parts);

        if ($count === 1) {
            return [$parts[0], ''];
        }

        if ($count >= 3) {
            $twoWordLast = Str::lower($parts[$count - 2].' '.$parts[$count - 1]);
            $threeWordLast = Str::lower($parts[$count - 3].' '.$parts[$count - 2].' '.$parts[$count - 1]);

            if ($this->containsPrefix($threeWordLast) && $count >= 4) {
                return [
                    implode(' ', array_slice($parts, 0, -3)),
                    implode(' ', array_slice($parts, -3)),
                ];
            }

            if ($this->containsPrefix($twoWordLast)) {
                return [
                    implode(' ', array_slice($parts, 0, -2)),
                    implode(' ', array_slice($parts, -2)),
                ];
            }
        }

        return [
            implode(' ', array_slice($parts, 0, -1)),
            implode('', array_slice($parts, -1)),
        ];
    }

    /**
     * Mirrors Python's `any(prefix in candidate for prefix in prefixes)` —
     * a substring test, not a whole-word match.
     */
    private function containsPrefix(string $candidate): bool
    {
        foreach (self::LAST_NAME_PREFIXES as $prefix) {
            if (str_contains($candidate, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
