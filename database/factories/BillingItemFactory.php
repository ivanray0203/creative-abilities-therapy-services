<?php

namespace Database\Factories;

use App\Models\BillingItem;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingItem>
 */
class BillingItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rate = fake()->randomFloat(2, 80, 150);
        $quantity = fake()->randomElement([0.5, 1, 1.5, 2]);

        return [
            'billing_number' => 'BIL-'.now()->year.'-'.fake()->unique()->numerify('######'),
            'therapist_id' => User::factory()->state(['role' => 'therapist']),
            'client_id' => Client::factory(),
            'session_id' => null,
            'invoice_service_id' => null,
            'service_name' => fake()->randomElement(['Home Visit', 'Documentation', 'Travel Time']),
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => round($rate * $quantity, 2),
            // A bill is normally raised by the therapist who delivered the
            // work; an admin raising one on their behalf overrides this.
            'issued_by_id' => fn (array $attributes): mixed => $attributes['therapist_id'],
            'invoice_id' => null,
            'notes' => null,
        ];
    }

    /** A line a monthly invoice has already claimed. */
    public function billed(int $invoiceId): static
    {
        return $this->state(['invoice_id' => $invoiceId]);
    }
}
