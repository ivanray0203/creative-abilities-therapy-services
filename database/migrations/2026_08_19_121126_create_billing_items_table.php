<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single billed line: one service delivered to one client, raised by the
 * therapist who delivered it (or by an admin on their behalf). Bills are
 * loose until the month closes — `invoice_id` is filled in only when the
 * generated invoice rolls this line into it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_items', function (Blueprint $table) {
            $table->id();
            $table->string('billing_number', 100)->unique();
            $table->foreignId('therapist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('schedule_sessions')->nullOnDelete();
            $table->foreignId('invoice_service_id')->nullable()->constrained('invoice_services')->nullOnDelete();
            // Kept alongside the FK so the line still reads correctly after a
            // rate-card entry is renamed or retired, and so a hand-typed
            // "Other" service has somewhere to live.
            $table->string('service_name');
            // Billable hours, so fractional: 0.75 and 1.5 are real quantities.
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            /** Who raised the bill — the therapist themselves, or an admin. */
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // The month-end roll-up reads a therapist's still-unbilled lines.
            $table->index(['therapist_id', 'invoice_id']);
            $table->index(['client_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_items');
    }
};
