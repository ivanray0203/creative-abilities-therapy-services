<?php

use App\Models\ServiceOffering;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the service offerings catalog includes the ss-fscd clinical coordinator service', function () {
    $this->seed(ServiceSeeder::class);

    $offering = ServiceOffering::query()->where('name', 'Clinical Coordinator')->first();

    expect($offering)->not->toBeNull();
    expect($offering->code)->toBe('clinical-coordinator');
    expect($offering->is_active)->toBeTrue();
});

test('seeding twice does not duplicate service offerings', function () {
    $this->seed(ServiceSeeder::class);
    $this->seed(ServiceSeeder::class);

    expect(ServiceOffering::query()->where('name', 'Clinical Coordinator')->count())->toBe(1);
});
