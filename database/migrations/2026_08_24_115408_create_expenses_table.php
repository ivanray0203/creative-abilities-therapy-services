<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinic spending — rent, supplies, software, and the rest — recorded as it
 * happens so it can be reported against a period.
 *
 * `amount` is the pre-tax subtotal and `tax_amount` the GST charged, kept
 * apart because the reporting screen totals them separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->date('expense_date');
            $table->string('category', 60);
            $table->string('payee');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->string('payment_method', 40);
            $table->string('status', 20)->default('paid');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date');
            $table->index(['category', 'expense_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
