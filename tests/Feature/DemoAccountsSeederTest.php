<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the demo accounts seeder creates the client intake without faker', function () {
    $this->seed(DemoAccountsSeeder::class);

    $clientUser = User::query()->where('email', 'client@cats.test')->first();
    $intake = Intake::query()->where('primary_parent_email', 'client@cats.test')->first();

    expect($clientUser)->not->toBeNull();
    expect($intake)->not->toBeNull();
    expect($intake->child_first_name)->toBe('Demo');
    expect($intake->status)->toBe('approved');
    expect($intake->reference_number)->toBe('INT-'.now()->year.'-001');
    expect(Client::query()->where('user_id', $clientUser->id)->count())->toBe(1);
});

test('seeding twice does not duplicate the demo intake or client', function () {
    $this->seed(DemoAccountsSeeder::class);
    $this->seed(DemoAccountsSeeder::class);

    expect(Intake::query()->where('primary_parent_email', 'client@cats.test')->count())->toBe(1);
    expect(User::query()->where('email', 'client@cats.test')->count())->toBe(1);
});
