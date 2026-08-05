<?php

namespace Database\Factories;

use App\Models\BillingAccount;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingAccount>
 */
class BillingAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'currency' => 'CAD',
            'balance' => 0,
        ];
    }
}
