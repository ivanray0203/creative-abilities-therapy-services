<?php

namespace Database\Factories;

use App\Models\Intake;
use App\Models\IntakeTherapistApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntakeTherapistApproval>
 */
class IntakeTherapistApprovalFactory extends Factory
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
            'status' => 'pending',
        ];
    }
}
