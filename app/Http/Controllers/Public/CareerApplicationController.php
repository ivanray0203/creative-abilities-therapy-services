<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\CareerApplicationAdminNotification;
use App\Mail\CareerApplicationSubmittedConfirmationMail;
use App\Models\Application;
use App\Models\Career;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GoogleDrive\DriveStorage;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Career application flow, ported from cats-frontend's
 * src/forms/CareerApplicationFrom.tsx (react-hook-form + zod + axios) to
 * Laravel validation + Inertia's useForm.
 */
class CareerApplicationController extends Controller
{
    public function create(Request $request, ?Career $career = null): Response
    {
        return Inertia::render('public/career-application', [
            'careers' => Career::query()->where('is_active', true)->get(),
            'preselectedCareer' => $career,
        ]);
    }

    public function store(Request $request, ReferenceNumberGenerator $referenceNumberGenerator, DriveStorage $drive): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'resident_status' => ['required', 'string', 'max:200'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'position_applied' => ['required', 'string', 'max:255'],
            'position_id' => ['nullable', 'integer', 'exists:careers,id'],
            'profession_status' => ['required', 'string', 'max:255'],
            'preferred_start_date' => ['required', 'date'],
            'is_working_with_other' => ['required', 'boolean'],
            'availability' => ['required', 'array', 'size:7'],
            'availability.*.week_day' => ['required', 'string'],
            'availability.*.time_from' => ['nullable', 'string'],
            'availability.*.time_to' => ['nullable', 'string'],
            'resume_file' => ['required', 'file', 'mimes:pdf,doc,docx'],
            'cover_letter_file' => ['nullable', 'file', 'mimes:pdf,doc,docx'],
            'drivers_license' => ['required', 'boolean'],
            'has_vehicle' => ['required', 'boolean'],
            'lead_source' => ['required', 'string', 'max:255'],
            'reason_for_applying' => ['required', 'string'],
            'other_notes' => ['nullable', 'string'],
            'expected_salary' => ['nullable', 'string', 'max:255'],
            'experience' => ['required', 'string'],
            'education' => ['required', 'string', 'max:255'],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'string'],
            'references' => ['required', 'array', 'min:1'],
            'references.0.full_name' => ['required', 'string', 'max:255'],
            'references.0.position' => ['required', 'string', 'max:255'],
            'references.0.work' => ['required', 'string', 'max:255'],
            'references.0.email' => ['required', 'email', 'max:255'],
            'references.0.phone' => ['required', 'string', 'max:20'],
            'references.*.full_name' => ['nullable', 'string', 'max:255'],
            'references.*.position' => ['nullable', 'string', 'max:255'],
            'references.*.work' => ['nullable', 'string', 'max:255'],
            'references.*.email' => ['nullable', 'email', 'max:255'],
            'references.*.phone' => ['nullable', 'string', 'max:20'],
        ]);

        $folderName = "Application-{$validated['first_name']}-{$validated['last_name']}";

        $resumeUrl = $drive->upload($request->file('resume_file'), 'Application', $folderName)['drive_file_url'];

        $coverLetterUrl = $request->hasFile('cover_letter_file')
            ? $drive->upload($request->file('cover_letter_file'), 'Application', $folderName)['drive_file_url']
            : null;

        $application = Application::query()->create([
            ...collect((array) $validated)->except(['resume_file', 'cover_letter_file'])->all(),
            'resume' => $resumeUrl,
            'cover_letter' => $coverLetterUrl,
            'application_status' => 'pending',
            'reference_number' => $referenceNumberGenerator->application(),
        ]);

        Mail::to($application->email)->send(new CareerApplicationSubmittedConfirmationMail($application));

        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where('new_applications', true)
            ->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails)->send(new CareerApplicationAdminNotification($application));
        }

        AuditLogger::log(
            'Application submitted',
            'Applications',
            "New career application #{$application->id} ({$application->reference_number})",
            'info',
            $application->email,
        );

        return back()->with('application', [
            'reference_number' => $application->reference_number,
        ]);
    }
}
