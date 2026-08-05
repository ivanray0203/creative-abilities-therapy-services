<?php

use App\Models\Application;
use App\Models\Career;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function validCareerApplicationPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '5874338780',
        'email' => 'jane.doe@example.com',
        'street_address' => '123 Main St',
        'resident_status' => 'Canadian Citizen',
        'city' => 'Calgary',
        'province' => 'Alberta',
        'zip_code' => 'T2N 1N4',
        'position_applied' => 'Occupational Therapist',
        'profession_status' => 'licensed',
        'preferred_start_date' => now()->addWeeks(2)->toDateString(),
        'is_working_with_other' => false,
        'availability' => [
            ['week_day' => 'Monday', 'time_from' => '09:00', 'time_to' => '17:00'],
            ['week_day' => 'Tuesday', 'time_from' => '09:00', 'time_to' => '17:00'],
            ['week_day' => 'Wednesday', 'time_from' => '', 'time_to' => ''],
            ['week_day' => 'Thursday', 'time_from' => '', 'time_to' => ''],
            ['week_day' => 'Friday', 'time_from' => '', 'time_to' => ''],
            ['week_day' => 'Saturday', 'time_from' => '', 'time_to' => ''],
            ['week_day' => 'Sunday', 'time_from' => '', 'time_to' => ''],
        ],
        'resume_file' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        'drivers_license' => true,
        'has_vehicle' => true,
        'lead_source' => 'website_ad',
        'reason_for_applying' => 'I want to help children reach their fullest potential.',
        'experience' => '5 years',
        'education' => 'bachelors',
        'skills' => ['Communication', 'Patience'],
        'references' => [
            [
                'full_name' => 'John Smith',
                'position' => 'Supervisor',
                'work' => 'ABC Therapy Clinic',
                'email' => 'john.smith@example.com',
                'phone' => '5874338781',
            ],
        ],
    ], $overrides);
}

test('a visitor can submit a career application with a resume upload', function () {
    Storage::fake('public');

    $career = Career::factory()->create(['is_active' => true]);

    $payload = validCareerApplicationPayload(['position_id' => $career->id]);

    $response = $this->post('/careers/apply', $payload);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('application');

    expect(Application::count())->toBe(1);

    $application = Application::first();
    expect($application->application_status)->toBe('pending');
    expect($application->reference_number)->toMatch('/^APP-'.now()->year.'-\d{3}$/');
    expect($application->first_name)->toBe('Jane');
    expect($application->email)->toBe('jane.doe@example.com');
    expect($application->skills)->toBe(['Communication', 'Patience']);
    expect($application->references)->toHaveCount(1);
    expect($application->resume)->not->toBeNull();

    $resumePath = Str::after($application->resume, Storage::disk('public')->url(''));
    Storage::disk('public')->assertExists($resumePath);
});

test('the career application form requires the resume file and first reference details', function () {
    $payload = validCareerApplicationPayload();
    unset($payload['resume_file'], $payload['references']);

    $response = $this->post('/careers/apply', $payload);

    $response->assertSessionHasErrors(['resume_file', 'references']);

    expect(Application::count())->toBe(0);
});
