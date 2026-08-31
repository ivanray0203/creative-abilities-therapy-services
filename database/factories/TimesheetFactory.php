<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timesheet>
 */
class TimesheetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        return [
            'timesheet_number' => 'TMS-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'therapist_id' => User::factory()->state(['role' => 'therapist']),
            'client_id' => Client::factory(),
            'issued_by_id' => fn (array $attributes): mixed => $attributes['therapist_id'],
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'rows' => [[
                'date' => $from->toDateString(),
                'hourly_respite' => 1.0,
                'community_support' => 0.0,
                'bda_direct' => 2.0,
                'bda_indirect' => 0.5,
            ]],
            'total_hourly_respite' => 1,
            'total_community_support' => 0,
            'total_bda_direct' => 2,
            'total_bda_indirect' => 0.5,
            'total_hours' => 3.5,
            // The aide signs as they generate, so a timesheet only ever
            // reaches the parent already signed on the aide's side.
            'aide_signature' => 'data:image/png;base64,'.base64_encode('aide'),
            'aide_signed_at' => now(),
            'status' => Timesheet::STATUS_AWAITING_CLIENT,
        ];
    }

    /** A form both sides have signed — what the admin reads. */
    public function signed(): static
    {
        return $this->state([
            'parent_signature' => 'data:image/png;base64,'.base64_encode('parent'),
            'parent_signed_at' => now(),
            'status' => Timesheet::STATUS_SIGNED,
        ]);
    }
}
