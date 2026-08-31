<?php

use App\Models\Career;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Career list columns are read by both the admin form and the public posting
 * page, which map straight over them. A plain `array` cast round-tripped
 * whatever it was given, so seed data that wrote a newline-joined string came
 * back as a string and every `.map()` over it threw.
 */
test('a list column written as an array reads back as an array', function () {
    $career = Career::factory()->create([
        'responsibilities' => ['Assess clients', 'Write reports'],
    ]);

    expect($career->refresh()->responsibilities)->toBe(['Assess clients', 'Write reports']);
});

test('a legacy newline string is read back as a list', function () {
    $career = Career::factory()->create();

    // Bypass the cast to reproduce exactly what the demo seeder used to store.
    DB::table('careers')->where('id', $career->id)->update([
        'responsibilities' => json_encode("Assess clients\nWrite reports\nCollaborate with families"),
    ]);

    expect($career->refresh()->responsibilities)->toBe([
        'Assess clients',
        'Write reports',
        'Collaborate with families',
    ]);
});

test('a list column written as a newline string is normalised on save', function () {
    $career = Career::factory()->create([
        'qualifications' => "Alberta registration\nClear record check",
    ]);

    expect($career->refresh()->qualifications)->toBe([
        'Alberta registration',
        'Clear record check',
    ]);
});

test('empty and null list columns read back as empty arrays', function () {
    $career = Career::factory()->create(['skills' => [], 'highlights' => null]);

    expect($career->refresh()->skills)->toBe([])
        ->and($career->refresh()->highlights)->toBe([]);
});

test('blank entries are dropped rather than rendered as empty bullets', function () {
    $career = Career::factory()->create([
        'benefits' => ['Flexible scheduling', '', '   ', 'Admin support'],
    ]);

    expect($career->refresh()->benefits)->toBe(['Flexible scheduling', 'Admin support']);
});

test('every list column on a posting is an array for the admin form', function () {
    $career = Career::factory()->create();

    foreach ([
        'responsibilities', 'qualifications', 'skills',
        'benefits', 'highlights', 'required_documents',
    ] as $column) {
        expect($career->refresh()->{$column})->toBeArray();
    }
});
