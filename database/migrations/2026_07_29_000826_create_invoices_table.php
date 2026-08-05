<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('billing_account_id')->nullable()->constrained('billing_accounts')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('schedule_sessions')->nullOnDelete();
            $table->string('reference', 100)->unique()->nullable();
            $table->string('invoice_id', 100)->unique()->nullable();
            $table->json('services')->nullable();
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(5.00);
            $table->decimal('gst', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->date('paid_date')->nullable();
            $table->enum('status', ['sent', 'draft', 'paid', 'overdue', 'unpaid', 'refunded'])->default('draft');
            $table->string('processed_by')->nullable();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('timeline')->nullable();
            $table->string('bill_to_name')->nullable();
            $table->string('bill_to_email')->nullable();
            $table->string('bill_to_phone')->nullable();
            $table->text('bill_to_address')->nullable();
            $table->enum('billed_by', ['therapist', 'admin'])->nullable();
            $table->foreignId('linked_therapist_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
