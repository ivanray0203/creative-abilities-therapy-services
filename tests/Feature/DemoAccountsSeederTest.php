<?php

use App\Models\Client;
use App\Models\Intake;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAccountsSeeder;
use Database\Seeders\ProductionAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

test('the demo accounts seeder creates the developer admin account', function () {
    $this->seed(DemoAccountsSeeder::class);

    $admin = User::query()->where('email', 'ivanray0621@gmail.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe('admin');
    expect($admin->is_active)->toBeTrue();
    expect(Hash::check('Ivanray0203!', $admin->password))->toBeTrue();
});

test('the production admin seeder creates both real admin accounts', function () {
    $this->seed(ProductionAdminSeeder::class);

    $admin = User::query()->where('email', 'admin@creativeabilitiestherapyservices.ca')->firstOrFail();
    $bernard = User::query()->where('email', 'bernard.lerit@creativeabilitiestherapyservices.ca')->firstOrFail();

    expect($admin->role)->toBe('admin');
    expect(Hash::check('CaTsInc123*', $admin->password))->toBeTrue();
    expect($bernard->role)->toBe('admin');
    expect($bernard->is_active)->toBeTrue();
    expect(Hash::check('CaTsInc123', $bernard->password))->toBeTrue();
});

test('the database seeder skips demo accounts in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    app(DatabaseSeeder::class)->__invoke();

    expect(User::query()->where('email', 'admin@creativeabilitiestherapyservices.ca')->exists())->toBeTrue();
    expect(User::query()->where('email', 'ivanray0621@gmail.com')->exists())->toBeFalse();
    expect(User::query()->where('email', 'therapist@cats.test')->exists())->toBeFalse();
});

test('the database seeder includes demo accounts outside production', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'ivanray0621@gmail.com')->exists())->toBeTrue();
    expect(User::query()->where('email', 'admin@creativeabilitiestherapyservices.ca')->exists())->toBeTrue();
});
