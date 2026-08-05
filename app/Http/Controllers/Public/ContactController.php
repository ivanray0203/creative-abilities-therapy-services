<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormNotification;
use App\Models\Contact;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Mirrors the reference's embedded footer contact form (src/forms/ContactForm.tsx).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'min:7', 'max:15'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        $contact = Contact::query()->create(['contact' => $validated]);

        $adminEmails = User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->pluck('email');

        if ($adminEmails->isNotEmpty()) {
            Mail::to($adminEmails)->send(new ContactFormNotification($contact));
        }

        AuditLogger::log('Contact form submitted', 'System', "New contact form submission from {$validated['email']}", 'info', $validated['email']);

        return back()->with('success', 'We will contact you as soon as possible.');
    }
}
