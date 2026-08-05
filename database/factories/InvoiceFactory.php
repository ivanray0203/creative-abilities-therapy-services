<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rate = fake()->randomFloat(2, 80, 150);
        $sessions = fake()->numberBetween(1, 4);
        $subTotal = round($rate * $sessions, 2);
        $gst = round($subTotal * 0.05, 2);

        return [
            'client_id' => Client::factory(),
            'reference' => 'REF-'.fake()->unique()->numerify('########'),
            'invoice_id' => 'INV-'.fake()->unique()->regexify('[A-F0-9]{8}'),
            'services' => [[
                'name' => 'Occupational Therapy',
                'description' => 'Individual session',
                'period' => now()->format('F Y'),
                'numberOfSessions' => $sessions,
                'rate' => '$'.number_format($rate, 2),
                'rate_numeric' => $rate,
            ]],
            'sub_total' => $subTotal,
            'tax_percentage' => 5.00,
            'gst' => $gst,
            'total' => $subTotal + $gst,
            'amount_due' => $subTotal + $gst,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'draft',
            'billed_by' => 'admin',
        ];
    }
}
