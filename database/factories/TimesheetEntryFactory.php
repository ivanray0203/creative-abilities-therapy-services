<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\TimesheetEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimesheetEntry>
 */
class TimesheetEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'therapist_id' => User::factory()->state(['role' => 'therapist']),
            'client_id' => Client::factory(),
            'entry_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'hourly_respite_hours' => fake()->randomElement([0, 0, 1, 1.5, 2]),
            'community_support_hours' => fake()->randomElement([0, 0, 1, 2]),
            'bda_direct_hours' => fake()->randomElement([0, 1, 1.5, 2, 3]),
            'bda_indirect_hours' => fake()->randomElement([0, 0.25, 0.5]),
            'notes' => null,
            'timesheet_id' => null,
        ];
    }

    /** An entry a generated timesheet has already claimed. */
    public function claimed(int $timesheetId): static
    {
        return $this->state(['timesheet_id' => $timesheetId]);
    }
}
