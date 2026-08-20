<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRegistrationRequest;
use App\Mail\ProgramRegistrationAdminNotification;
use App\Models\Program;
use App\Models\ProgramRegistration;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReferenceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ProgramRegistrationController extends Controller
{
    public function store(
        StoreProgramRegistrationRequest $request,
        Program $program,
        ReferenceNumberGenerator $referenceNumberGenerator,
    ): RedirectResponse {
        $registration = ProgramRegistration::query()->create([
            ...$request->validated(),
            'program_id' => $program->id,
            'reference_number' => $referenceNumberGenerator->programRegistration(),
            'status' => 'pending',
        ]);

        AuditLogger::log(
            'Program registration submitted',
            'System',
            "New registration {$registration->reference_number} for {$program->name}",
            'info',
            $registration->parent_email,
        );

        $this->notifyAdmins($registration);

        return back()->with('success', "Registration received. Your reference number is {$registration->reference_number}.");
    }

    /**
     * Mirrors the other public forms: admins who have opted in get told, and
     * a mail failure never costs the family their submission.
     */
    private function notifyAdmins(ProgramRegistration $registration): void
    {
        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->pluck('email');

        if ($adminEmails->isEmpty()) {
            return;
        }

        Mail::to($adminEmails)->send(
            new ProgramRegistrationAdminNotification($registration->load('program')),
        );
    }
}
