<?php

namespace Database\Factories;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->therapist(),
            'position' => fake()->jobTitle(),
            'department' => 'clinical_services',
            'employment_status' => 'active',
            'hire_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'hourly_rate' => fake()->randomFloat(2, 30, 120),
            'maximum_caseload' => fake()->numberBetween(5, 30),
            'credentials' => [fake()->randomElement(['OTRL', 'PhD', 'BCBA', 'SLP'])],
            'specializations' => [],
            'can_access_finance' => false,
            'can_manage_team' => false,
            'can_manage_clients' => true,
            'phone' => fake()->phoneNumber(),
        ];
    }

    /** A candidate whose account exists but whose documents are still under review. */
    public function onboarding(): static
    {
        return $this->state(fn (): array => ['employment_status' => 'onboarding']);
    }
}
