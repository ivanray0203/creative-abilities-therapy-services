<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'first_name' => 'Test',
        //     'last_name' => 'User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(DemoAccountsSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(CareerSeeder::class);
        $this->call(ProgramSeeder::class);
        $this->call(InvoiceServiceSeeder::class);
    }
}
