<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\CareerController as AdminCareerController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseReportController;
use App\Http\Controllers\Admin\GoogleDriveConnectionController;
use App\Http\Controllers\Admin\HourTrackingController as AdminHourTrackingController;
use App\Http\Controllers\Admin\IntakeController;
use App\Http\Controllers\Admin\InvoiceServiceController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\ServiceContractController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceOfferingController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BillingItemController;
use App\Http\Controllers\Client\IntakeController as ClientIntakeController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\ClientProfileController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ConsentAcceptanceController;
use App\Http\Controllers\HourTrackingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Public\CareerApplicationController;
use App\Http\Controllers\Public\CareerController;
use App\Http\Controllers\Public\CheckEmailController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\IntakeApplicationController;
use App\Http\Controllers\Public\OfferLetterController;
use App\Http\Controllers\Public\ProgramController;
use App\Http\Controllers\Public\ProgramRegistrationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TherapistClientController;
use App\Http\Controllers\TherapistDashboardController;
use App\Http\Controllers\TherapistIntakeController;
use App\Http\Controllers\TherapistListController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TimesheetEntryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'public/home')->name('public.home');
Route::inertia('about', 'public/about')->name('public.about');
Route::inertia('team', 'public/team')->name('public.team');
Route::inertia('team/founder', 'public/founder')->name('public.founder');
Route::inertia('services', 'public/services')->name('public.services');
/*
 * The services pages are static marketing content held in
 * resources/js/lib/content/service-list.ts, so the route only forwards the
 * requested service code and the page resolves it client-side.
 */
Route::get('servicesDetails/{service}', fn (string $service) => Inertia::render(
    'public/service-detail',
    ['code' => $service],
))->name('public.service-detail');
Route::get('programs', [ProgramController::class, 'index'])->name('public.programs');
Route::get('programs/{program}', [ProgramController::class, 'show'])->name('public.program-detail');
// Throttled like the other public write endpoints: this one is unauthenticated.
Route::post('programs/{program}/register', [ProgramRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.programs.register');
Route::inertia('fscd', 'public/fscd')->name('public.fscd');
Route::get('careers', [CareerController::class, 'index'])->name('public.careers');
Route::get('careers/apply/{career?}', [CareerApplicationController::class, 'create'])->name('public.careers.apply');
Route::post('careers/apply', [CareerApplicationController::class, 'store'])->name('public.careers.apply.store');
Route::get('careers/{career}', [CareerController::class, 'show'])->name('public.career-detail');
Route::inertia('faq', 'public/faq')->name('public.faq');
Route::inertia('termsandconditions', 'public/terms')->name('public.terms');
Route::inertia('privacypolicy', 'public/privacy')->name('public.privacy');
Route::inertia('cookiepolicy', 'public/cookie')->name('public.cookie');
Route::inertia('accessibility', 'public/accessibility')->name('public.accessibility');
Route::post('contacts', [ContactController::class, 'store'])->name('public.contacts.store');

// The candidate has no account yet — the hire is what creates one — so the
// signed URL stands in for a login. It expires with the offer itself.
Route::middleware('signed')->group(function (): void {
    Route::get('offer/{application}', [OfferLetterController::class, 'show'])->name('public.offer.show');
    Route::post('offer/{application}/accept', [OfferLetterController::class, 'accept'])->name('public.offer.accept');
    Route::post('offer/{application}/decline', [OfferLetterController::class, 'decline'])->name('public.offer.decline');
});

Route::get('intake/apply', [IntakeApplicationController::class, 'create'])->name('public.intake.create');
Route::post('intake/apply', [IntakeApplicationController::class, 'store'])->name('public.intake.store');
// Throttled: this endpoint confirms whether an address is registered, so an
// unlimited version would let anyone enumerate clients and applicants.
Route::get('check-email', CheckEmailController::class)
    ->middleware('throttle:10,1')
    ->name('public.check-email');

Route::get('therapists', TherapistListController::class)->middleware('auth')->name('therapists.index');

Route::prefix('consent-acceptances')->name('consent-acceptances.')->group(function () {
    Route::get('required', [ConsentAcceptanceController::class, 'required'])->name('required');
    Route::post('/', [ConsentAcceptanceController::class, 'store'])->name('store');

    Route::middleware('auth')->group(function () {
        Route::get('mine', [ConsentAcceptanceController::class, 'mine'])->name('mine');
        Route::post('{consentAcceptance}/revoke', [ConsentAcceptanceController::class, 'revoke'])->name('revoke');
    });
});

// Placeholder role-home routes proving layout + role middleware wiring.
// Real pages/controllers for each land in Phases 4, 6-14.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('calendar', [CalendarController::class, 'index'])->name('admin.calendar');

        Route::get('google-drive/connect', [GoogleDriveConnectionController::class, 'connect'])->name('admin.google-drive.connect');
        Route::get('google-drive/callback', [GoogleDriveConnectionController::class, 'callback'])->name('admin.google-drive.callback');

        Route::get('intake', [IntakeController::class, 'index'])->name('admin.intake.index');
        Route::get('intake/create', [IntakeController::class, 'create'])->name('admin.intake.create');
        Route::post('intake', [IntakeController::class, 'store'])->name('admin.intake.store');
        Route::get('intake/therapist-reviews', [IntakeController::class, 'therapistReviews'])->name('admin.intake.therapist-reviews');
        Route::get('intake/{intake}', [IntakeController::class, 'show'])->name('admin.intake.show');
        Route::get('intake/{intake}/edit', [IntakeController::class, 'edit'])->name('admin.intake.edit');
        Route::put('intake/{intake}', [IntakeController::class, 'update'])->name('admin.intake.update');
        Route::delete('intake/{intake}', [IntakeController::class, 'destroy'])->name('admin.intake.destroy');
        Route::patch('intake/{intake}/status', [IntakeController::class, 'updateStatus'])->name('admin.intake.update-status');
        Route::post('intake/{intake}/approve', [IntakeController::class, 'approve'])->name('admin.intake.approve');
        Route::post('intake/{intake}/send-to-therapist', [IntakeController::class, 'sendToTherapist'])->name('admin.intake.send-to-therapist');
        Route::post('intake/{intake}/therapist-approve', [IntakeController::class, 'therapistApprove'])->name('admin.intake.therapist-approve');
        Route::post('intake/{intake}/therapist-reject', [IntakeController::class, 'therapistReject'])->name('admin.intake.therapist-reject');
        Route::post('intake/{intake}/documents', [IntakeController::class, 'uploadDocument'])->name('admin.intake.documents.store');
        Route::post('intake/{intake}/documents/multiple', [IntakeController::class, 'uploadMultipleDocuments'])->name('admin.intake.documents.store-multiple');
        Route::patch('intake/{intake}/documents', [IntakeController::class, 'updateDocument'])->name('admin.intake.documents.update');
        Route::delete('intake/documents/{document}', [IntakeController::class, 'deleteDocument'])->name('admin.intake.documents.destroy');
        Route::patch('intake/{intake}/notes', [IntakeController::class, 'addNote'])->name('admin.intake.notes.store');
        Route::delete('intake/{intake}/notes/{note}', [IntakeController::class, 'deleteNote'])->name('admin.intake.notes.destroy');

        Route::get('clients', [ClientController::class, 'index'])->name('admin.client.index');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('admin.client.show');
        Route::get('clients/{client}/pdf', [ClientController::class, 'exportPdf'])->name('admin.client.export-pdf');
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('admin.client.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('admin.client.update');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('admin.client.destroy');
        Route::post('clients/{client}/assign-therapist', [ClientController::class, 'assignTherapist'])->name('admin.client.assign-therapist');
        Route::post('clients/{client}/reassign-therapist', [ClientController::class, 'reassignTherapist'])->name('admin.client.reassign-therapist');
        Route::patch('clients/{client}/care-team', [ClientController::class, 'updateCareTeam'])->name('admin.client.care-team.update');
        Route::post('clients/{client}/services', [ClientController::class, 'storeService'])->name('admin.client.services.store');
        Route::put('clients/{client}/services/{clientService}', [ClientController::class, 'updateService'])->name('admin.client.services.update');
        Route::delete('clients/{client}/services/{clientService}', [ClientController::class, 'destroyService'])->name('admin.client.services.destroy');
        Route::post('clients/{client}/documents', [ClientController::class, 'uploadDocument'])->name('admin.client.documents.store');
        Route::delete('clients/documents/{document}', [ClientController::class, 'deleteDocument'])->name('admin.client.documents.destroy');
        Route::patch('clients/{client}/notes', [ClientController::class, 'addNote'])->name('admin.client.notes.store');
        Route::delete('clients/{client}/notes/{note}', [ClientController::class, 'deleteNote'])->name('admin.client.notes.destroy');
        Route::get('clients/{client}/services/{clientService}/sessions', [SessionController::class, 'clientServiceSessions'])->name('admin.client.services.sessions');

        /*
         * Phase 20 — a service contract is the admin's authorization for one
         * availed service, so it is nested under that service rather than
         * given a page of its own. Cancelling and deleting are different
         * acts: a contract with sessions against it can only be cancelled.
         */
        Route::post('clients/{client}/services/{clientService}/contracts', [ServiceContractController::class, 'store'])->name('admin.client.services.contracts.store');
        Route::put('clients/{client}/services/{clientService}/contracts/{contract}', [ServiceContractController::class, 'update'])->name('admin.client.services.contracts.update');
        Route::post('clients/{client}/services/{clientService}/contracts/{contract}/cancel', [ServiceContractController::class, 'cancel'])->name('admin.client.services.contracts.cancel');
        Route::delete('clients/{client}/services/{clientService}/contracts/{contract}', [ServiceContractController::class, 'destroy'])->name('admin.client.services.contracts.destroy');

        Route::get('sessions', [SessionController::class, 'index'])->name('admin.sessions.index');
        Route::get('sessions/create', [SessionController::class, 'create'])->name('admin.sessions.create');
        Route::post('sessions', [SessionController::class, 'store'])->name('admin.sessions.store');
        Route::get('sessions/by-user', [SessionController::class, 'byUser'])->name('admin.sessions.by-user');
        Route::get('sessions/by-user-and-service', [SessionController::class, 'byUserAndService'])->name('admin.sessions.by-user-and-service');
        Route::get('sessions/by-client-service/{clientService}', [SessionController::class, 'byClientService'])->name('admin.sessions.by-client-service');
        Route::get('sessions/therapist/{user}', [SessionController::class, 'byTherapist'])->name('admin.sessions.by-therapist');
        Route::get('sessions/{session}/edit', [SessionController::class, 'edit'])->name('admin.sessions.edit');
        Route::put('sessions/{session}', [SessionController::class, 'update'])->name('admin.sessions.update');
        Route::delete('sessions/{session}', [SessionController::class, 'destroy'])->name('admin.sessions.destroy');
        Route::post('sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('admin.sessions.cancel');
        Route::post('sessions/{session}/dispute', [SessionController::class, 'dispute'])->name('admin.sessions.dispute');

        Route::get('services', [InvoiceServiceController::class, 'index'])->name('admin.services.index');
        Route::patch('services/{invoiceService}/rates', [InvoiceServiceController::class, 'updateRates'])->name('admin.services.update-rates');
        Route::get('services/add', [ServiceOfferingController::class, 'create'])->name('admin.services.create');
        Route::post('services', [ServiceOfferingController::class, 'store'])->name('admin.services.store');
        Route::get('services/edit/{service}', [ServiceOfferingController::class, 'edit'])->name('admin.services.edit');
        Route::put('services/{service}', [ServiceOfferingController::class, 'update'])->name('admin.services.update');

        Route::get('billing', [BillingItemController::class, 'index'])->name('admin.billing.index');
        Route::get('billing/create', [BillingItemController::class, 'create'])->name('admin.billing.create');
        Route::post('billing', [BillingItemController::class, 'store'])->name('admin.billing.store');
        Route::delete('billing/{billingItem}', [BillingItemController::class, 'destroy'])->name('admin.billing.destroy');

        /*
         * Phase 21 — the clinic's hour-tracking sheet across every therapist,
         * the same report each therapist sees of their own contracts. Filter
         * to one therapist to get exactly their copy.
         */
        Route::get('hour-tracking', [AdminHourTrackingController::class, 'index'])->name('admin.hour-tracking.index');
        Route::get('hour-tracking/pdf', [AdminHourTrackingController::class, 'pdf'])->name('admin.hour-tracking.pdf');

        // The aides' signed time sheets. Read-only apart from removal — the
        // form is generated by the aide and signed by the parent.
        Route::get('timesheets', [TimesheetController::class, 'index'])->name('admin.timesheets.index');
        Route::get('timesheets/{timesheet}/pdf', [TimesheetController::class, 'pdf'])->name('admin.timesheets.pdf');
        Route::get('timesheets/{timesheet}', [TimesheetController::class, 'show'])->name('admin.timesheets.show');
        Route::delete('timesheets/{timesheet}', [TimesheetController::class, 'destroy'])->name('admin.timesheets.destroy');

        // `expenses/report` must be declared before `expenses/{expense}` or the
        // word "report" would be bound as an expense id.
        Route::get('expenses', [ExpenseController::class, 'index'])->name('admin.expenses.index');
        Route::get('expenses/add', [ExpenseController::class, 'create'])->name('admin.expenses.create');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
        Route::get('expenses/report', [ExpenseReportController::class, 'index'])->name('admin.expenses.report');
        Route::get('expenses/report/export', [ExpenseReportController::class, 'export'])->name('admin.expenses.report.export');
        Route::get('expenses/edit/{expense}', [ExpenseController::class, 'edit'])->name('admin.expenses.edit');
        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('admin.expenses.update');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

        Route::get('invoices', [InvoiceController::class, 'index'])->name('admin.invoices.index');
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('admin.invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('admin.invoices.store');
        Route::post('invoices/generate-from-session', [InvoiceController::class, 'generateFromSession'])->name('admin.invoices.generate-from-session');
        Route::post('invoices/generate-from-billing', [InvoiceController::class, 'generateFromBilling'])->name('admin.invoices.generate-from-billing');
        Route::get('invoices/therapist/{user}', [InvoiceController::class, 'byTherapist'])->name('admin.invoices.by-therapist');
        Route::get('invoices/client/{client}', [InvoiceController::class, 'byClient'])->name('admin.invoices.by-client');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('admin.invoices.pdf');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('admin.invoices.show');
        Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('admin.invoices.edit');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('admin.invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('admin.invoices.destroy');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('admin.invoices.mark-paid');
        Route::post('invoices/{invoice}/resend', [InvoiceController::class, 'resend'])->name('admin.invoices.resend');

        Route::get('applications', [ApplicationController::class, 'index'])->name('admin.applications.index');
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('admin.applications.show');
        Route::delete('applications/{application}', [ApplicationController::class, 'destroy'])->name('admin.applications.destroy');
        Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('admin.applications.update-status');
        Route::patch('applications/{application}/rating', [ApplicationController::class, 'updateRating'])->name('admin.applications.update-rating');
        Route::patch('applications/{application}/notes', [ApplicationController::class, 'addNote'])->name('admin.applications.notes.store');
        Route::delete('applications/{application}/notes/{note}', [ApplicationController::class, 'deleteNote'])->name('admin.applications.notes.destroy');

        Route::get('team', [TeamMemberController::class, 'index'])->name('admin.team.index');
        Route::get('team/add', [TeamMemberController::class, 'create'])->name('admin.team.create');
        Route::post('team', [TeamMemberController::class, 'store'])->name('admin.team.store');
        Route::get('team/edit/{teamMember}', [TeamMemberController::class, 'edit'])->name('admin.team.edit');
        Route::get('team/{teamMember}/pdf', [TeamMemberController::class, 'exportPdf'])->name('admin.team.export-pdf');
        Route::get('team/{teamMember}', [TeamMemberController::class, 'show'])->name('admin.team.show');
        Route::put('team/{teamMember}', [TeamMemberController::class, 'update'])->name('admin.team.update');
        Route::delete('team/{teamMember}', [TeamMemberController::class, 'destroy'])->name('admin.team.destroy');
        Route::patch('team/{teamMember}/access', [TeamMemberController::class, 'updateAccess'])->name('admin.team.update-access');
        Route::patch('team/{teamMember}/rates', [TeamMemberController::class, 'updateRates'])->name('admin.team.update-rates');
        Route::post('team/{teamMember}/documents', [TeamMemberController::class, 'uploadDocument'])->name('admin.team.documents.store');
        Route::delete('team/documents/{document}', [TeamMemberController::class, 'deleteDocument'])->name('admin.team.documents.destroy');

        Route::get('careers', [AdminCareerController::class, 'index'])->name('admin.careers.index');
        Route::get('careers/add', [AdminCareerController::class, 'create'])->name('admin.careers.create');
        Route::post('careers', [AdminCareerController::class, 'store'])->name('admin.careers.store');
        Route::get('careers/edit/{career}', [AdminCareerController::class, 'edit'])->name('admin.careers.edit');
        Route::put('careers/{career}', [AdminCareerController::class, 'update'])->name('admin.careers.update');
        Route::delete('careers/{career}', [AdminCareerController::class, 'destroy'])->name('admin.careers.destroy');

        Route::get('programs', [AdminProgramController::class, 'index'])->name('admin.programs.index');
        Route::get('programs/add', [AdminProgramController::class, 'create'])->name('admin.programs.create');
        Route::post('programs', [AdminProgramController::class, 'store'])->name('admin.programs.store');
        Route::get('programs/edit/{program}', [AdminProgramController::class, 'edit'])->name('admin.programs.edit');
        Route::get('programs/{program}', [AdminProgramController::class, 'show'])->name('admin.programs.show');
        Route::put('programs/{program}', [AdminProgramController::class, 'update'])->name('admin.programs.update');
        Route::delete('programs/{program}', [AdminProgramController::class, 'destroy'])->name('admin.programs.destroy');
        Route::put('programs/{program}/registrations/{registration}', [AdminProgramController::class, 'updateRegistrationStatus'])
            ->name('admin.programs.registrations.update');

        Route::get('users/admin-list', [UserController::class, 'adminList'])->name('admin.users.admin-list');
        Route::patch('users/{user}/admin-update', [UserController::class, 'adminUpdate'])->name('admin.users.admin-update');

        Route::get('messages', [ComplaintController::class, 'index'])->name('admin.complaints.index');
        Route::post('messages/{complaint}/start-review', [ComplaintController::class, 'startReview'])->name('admin.complaints.start-review');
        Route::post('messages/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('admin.complaints.resolve');

        Route::get('administrator', [AdministratorController::class, 'index'])->name('admin.administrator.index');
        Route::post('administrator/users', [UserController::class, 'adminStore'])->name('admin.administrator.users.store');
        Route::patch('administrator/services/{service}', [AdminServiceController::class, 'updateVisibility'])->name('admin.administrator.services.update-visibility');
        Route::put('administrator/profile', [ProfileController::class, 'update'])->name('admin.administrator.profile.update');
    });

Route::middleware(['auth', 'role:therapist', 'onboarding.complete'])
    ->prefix('therapist')
    ->group(function () {
        Route::get('/', [TherapistDashboardController::class, 'index'])->name('therapist.home');

        Route::get('clients', [TherapistClientController::class, 'index'])->name('therapist.clients.index');

        Route::get('intake', [TherapistIntakeController::class, 'index'])->name('therapist.intake.index');
        Route::get('intake/{intake}', [TherapistIntakeController::class, 'show'])->name('therapist.intake.show');
        Route::post('intake/{intake}/therapist-approve', [IntakeController::class, 'therapistApprove'])->name('therapist.intake.therapist-approve');
        Route::post('intake/{intake}/therapist-reject', [IntakeController::class, 'therapistReject'])->name('therapist.intake.therapist-reject');

        Route::get('calendar', [TherapistDashboardController::class, 'calendar'])->name('therapist.calendar');
        Route::get('sessions', [SessionController::class, 'index'])->name('therapist.sessions.index');
        Route::get('sessions/create', [SessionController::class, 'create'])->name('therapist.sessions.create');
        Route::post('sessions', [SessionController::class, 'store'])->name('therapist.sessions.store');
        Route::get('sessions/by-user', [SessionController::class, 'byUser'])->name('therapist.sessions.by-user');
        Route::get('sessions/by-user-and-service', [SessionController::class, 'byUserAndService'])->name('therapist.sessions.by-user-and-service');
        Route::get('sessions/by-client-service/{clientService}', [SessionController::class, 'byClientService'])->name('therapist.sessions.by-client-service');
        Route::get('sessions/{session}/edit', [SessionController::class, 'edit'])->name('therapist.sessions.edit');
        Route::put('sessions/{session}', [SessionController::class, 'update'])->name('therapist.sessions.update');
        Route::delete('sessions/{session}', [SessionController::class, 'destroy'])->name('therapist.sessions.destroy');
        Route::post('sessions/{session}/cancel', [SessionController::class, 'cancel'])->name('therapist.sessions.cancel');
        Route::post('sessions/{session}/dispute', [SessionController::class, 'dispute'])->name('therapist.sessions.dispute');
        Route::post('sessions/{session}/start', [SessionController::class, 'startSession'])->name('therapist.sessions.start');
        Route::post('sessions/{session}/end', [SessionController::class, 'endSession'])->name('therapist.sessions.end');

        /*
         * Billing and invoicing belong to a therapist who bills services. An
         * aide records hours on a time sheet instead, so `aide:never` keeps
         * these unreachable for them rather than merely hidden.
         */
        Route::middleware('aide:never')->group(function () {
            /*
             * Phase 21 — the therapist's own hour-tracking sheet, read off
             * their contracts and the session ledger. Read-only: hours move
             * when a session is booked or cancelled, never from here.
             */
            Route::get('hour-tracking', [HourTrackingController::class, 'index'])->name('therapist.hour-tracking.index');
            Route::get('hour-tracking/pdf', [HourTrackingController::class, 'pdf'])->name('therapist.hour-tracking.pdf');

            Route::get('billing', [BillingItemController::class, 'index'])->name('therapist.billing.index');
            Route::get('billing/create', [BillingItemController::class, 'create'])->name('therapist.billing.create');
            Route::post('billing', [BillingItemController::class, 'store'])->name('therapist.billing.store');
            Route::delete('billing/{billingItem}', [BillingItemController::class, 'destroy'])->name('therapist.billing.destroy');

            Route::get('invoices', [InvoiceController::class, 'index'])->name('therapist.invoices.index');
            Route::get('invoices/create', [InvoiceController::class, 'create'])->name('therapist.invoices.create');
            Route::post('invoices', [InvoiceController::class, 'store'])->name('therapist.invoices.store');
            Route::post('invoices/generate-from-session', [InvoiceController::class, 'generateFromSession'])->name('therapist.invoices.generate-from-session');
            Route::post('invoices/generate-from-billing', [InvoiceController::class, 'generateFromBilling'])->name('therapist.invoices.generate-from-billing');
            Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('therapist.invoices.pdf');
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('therapist.invoices.show');
            Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('therapist.invoices.edit');
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('therapist.invoices.update');
            Route::post('invoices/{invoice}/resend', [InvoiceController::class, 'resend'])->name('therapist.invoices.resend');
        });

        /*
         * The aide's half: hours logged day by day, then rolled into a time
         * sheet the parent signs. `timesheets/generate` is declared before
         * `timesheets/{timesheet}` or the word would be bound as an id.
         */
        Route::middleware('aide')->group(function () {
            Route::get('hours', [TimesheetEntryController::class, 'index'])->name('therapist.hours.index');
            Route::get('hours/create', [TimesheetEntryController::class, 'create'])->name('therapist.hours.create');
            Route::post('hours', [TimesheetEntryController::class, 'store'])->name('therapist.hours.store');
            Route::delete('hours/{entry}', [TimesheetEntryController::class, 'destroy'])->name('therapist.hours.destroy');

            Route::get('timesheets', [TimesheetController::class, 'index'])->name('therapist.timesheets.index');
            Route::post('timesheets/generate', [TimesheetController::class, 'generate'])->name('therapist.timesheets.generate');
            Route::get('timesheets/{timesheet}/pdf', [TimesheetController::class, 'pdf'])->name('therapist.timesheets.pdf');
            Route::get('timesheets/{timesheet}', [TimesheetController::class, 'show'])->name('therapist.timesheets.show');
        });

        Route::get('profile', [TeamMemberController::class, 'me'])->name('therapist.team.me');
        Route::put('profile', [TeamMemberController::class, 'updateMe'])->name('therapist.team.update-me');
        Route::post('profile/documents', [TeamMemberController::class, 'uploadMyDocument'])->name('therapist.team.upload-my-document');
        Route::delete('profile/documents/{document}', [TeamMemberController::class, 'deleteMyDocument'])->name('therapist.team.delete-my-document');

        Route::get('complaints', [ComplaintController::class, 'index'])->name('therapist.complaints.index');
        Route::get('complaints/create', [ComplaintController::class, 'create'])->name('therapist.complaints.create');
        Route::post('complaints', [ComplaintController::class, 'store'])->name('therapist.complaints.store');
    });

Route::middleware(['auth', 'role:client'])
    ->prefix('client')
    ->group(function () {
        Route::get('/calendar', [ClientDashboardController::class, 'calendar'])->name('client.home');

        Route::post('select-child', [ClientDashboardController::class, 'selectChild'])->name('client.select-child');

        Route::get('intake', [ClientIntakeController::class, 'index'])->name('client.intake.index');
        Route::get('intake/create', [ClientIntakeController::class, 'create'])->name('client.intake.create');
        Route::post('intake', [ClientIntakeController::class, 'store'])->name('client.intake.store');

        Route::get('invoices', [InvoiceController::class, 'index'])->name('client.invoices.index');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('client.invoices.pdf');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('client.invoices.show');
        Route::post('invoices/{invoice}/sign', [InvoiceController::class, 'sign'])->name('client.invoices.sign');

        Route::get('timesheets', [TimesheetController::class, 'index'])->name('client.timesheets.index');
        Route::get('timesheets/{timesheet}/pdf', [TimesheetController::class, 'pdf'])->name('client.timesheets.pdf');
        Route::get('timesheets/{timesheet}', [TimesheetController::class, 'show'])->name('client.timesheets.show');
        Route::post('timesheets/{timesheet}/sign', [TimesheetController::class, 'sign'])->name('client.timesheets.sign');

        Route::get('sessions/by-user', [SessionController::class, 'byUser'])->name('client.sessions.by-user');
        Route::post('sessions/{session}/verify', [SessionController::class, 'verify'])->name('client.sessions.verify');
        Route::post('sessions/{session}/dispute', [SessionController::class, 'dispute'])->name('client.sessions.dispute');

        Route::get('complaints', [ComplaintController::class, 'index'])->name('client.complaints.index');
        Route::get('complaints/create', [ComplaintController::class, 'create'])->name('client.complaints.create');
        Route::post('complaints', [ComplaintController::class, 'store'])->name('client.complaints.store');

        Route::get('profile', [ClientProfileController::class, 'show'])->name('client.profile');
        Route::put('profile', [ClientProfileController::class, 'update'])->name('client.profile.update');
    });

require __DIR__.'/auth.php';
