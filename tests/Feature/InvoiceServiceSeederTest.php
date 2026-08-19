<?php

use App\Models\InvoiceService;
use Database\Seeders\InvoiceServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the invoice rate card seeds every line from the rate sheet', function () {
    $this->seed(InvoiceServiceSeeder::class);

    expect(InvoiceService::query()->count())->toBe(147);

    $homeVisit = InvoiceService::query()->where('code', 'occupational-therapist-home-visit')->first();

    expect($homeVisit->name)->toBe('Occupational Therapist Home Visit');
    expect($homeVisit->discipline)->toBe('ot');
    expect($homeVisit->rate_fscd)->toBe('94.76');
    expect($homeVisit->rate_private)->toBe('142.14');
    expect($homeVisit->is_active)->toBeTrue();
});

test('lines the rate sheet leaves blank under private funding have no private rate', function () {
    $this->seed(InvoiceServiceSeeder::class);

    $mileage = InvoiceService::query()->where('code', 'psychologist-mileage')->first();

    expect($mileage->rate_fscd)->toBe('0.50');
    expect($mileage->rate_private)->toBeNull();
    expect($mileage->rateFor('private'))->toBeNull();
    expect($mileage->rateFor('fscd'))->toBe('0.50');
});

test('the sheet ordering is preserved so lines stay grouped by discipline', function () {
    $this->seed(InvoiceServiceSeeder::class);

    $first = InvoiceService::query()->orderBy('sort_order')->first();

    expect($first->name)->toBe('Speech-Language Pathologist Home Visit');
    expect($first->sort_order)->toBe(1);
});

test('seeding twice updates rates in place instead of duplicating lines', function () {
    $this->seed(InvoiceServiceSeeder::class);

    InvoiceService::query()->where('code', 'bc-practice-consult')->update(['rate_fscd' => 1]);

    $this->seed(InvoiceServiceSeeder::class);

    expect(InvoiceService::query()->count())->toBe(147);
    expect(InvoiceService::query()->where('code', 'bc-practice-consult')->value('rate_fscd'))->toBe('77.25');
});

test('the active scope hides retired rate lines', function () {
    InvoiceService::factory()->code('ot-team-meeting')->create(['is_active' => false]);
    $active = InvoiceService::factory()->code('pt-team-meeting')->create();

    expect(InvoiceService::query()->active()->pluck('id')->all())->toBe([$active->id]);
});

test('the factory builds rate lines straight from the rate sheet', function () {
    $line = InvoiceService::factory()->code('bc-phone-consult')->create();

    expect($line->name)->toBe('BC - Phone Consult');
    expect($line->discipline)->toBe('bc');
    expect($line->rate_fscd)->toBe('77.25');
    expect($line->rate_private)->toBe('115.88');
});

test('the factory walks the sheet so repeated calls stay unique', function () {
    $lines = InvoiceService::factory()->count(3)->create();

    expect($lines->pluck('code')->unique())->toHaveCount(3);
    expect($lines->every(fn (InvoiceService $line): bool => collect(InvoiceServiceSeeder::RATE_CARD)
        ->contains(fn (array $row): bool => $row[0] === $line->name)))->toBeTrue();
});
