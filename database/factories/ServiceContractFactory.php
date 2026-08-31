<?php

namespace Database\Factories;

use App\Models\ClientService;
use App\Models\ServiceContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceContract>
 */
class ServiceContractFactory extends Factory
{
    /**
     * The default is the shape the feature was designed around: forty hours
     * over a window that is open today, so a contract made in a test needs no
     * period of its own.
     *
     * It runs to the end of *next* month rather than this one. Tests book a
     * day or two ahead, and a window that stopped at the month's end would
     * make every such test fail on the 30th and 31st.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_number' => 'CON-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'client_service_id' => ClientService::factory(),
            'therapist_id' => fn (array $attributes): mixed => ClientService::query()
                ->whereKey($attributes['client_service_id'])
                ->value('therapist_id'),
            'allotted_hours' => 40,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->addMonth()->endOfMonth()->toDateString(),
            'status' => ServiceContract::STATUS_ACTIVE,
        ];
    }

    /** A pool that ran out. The period is still open; the hours are not. */
    public function exhausted(): static
    {
        return $this->state(['allotted_hours' => 0, 'status' => ServiceContract::STATUS_EXHAUSTED]);
    }

    /** A period that has closed with hours still on it, which do not carry over. */
    public function expired(): static
    {
        return $this->state([
            'period_start' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'status' => ServiceContract::STATUS_EXPIRED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => ServiceContract::STATUS_CANCELLED]);
    }
}
