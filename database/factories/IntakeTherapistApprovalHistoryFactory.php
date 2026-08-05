<?php

namespace Database\Factories;

use App\Models\Intake;
use App\Models\IntakeTherapistApprovalHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntakeTherapistApprovalHistory>
 */
class IntakeTherapistApprovalHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_id' => Intake::factory(),
            'therapist_id' => User::factory()->therapist(),
            'status' => 'sent',
            'decided_at' => now(),
        ];
    }
}
