<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Admin "Record Expense" form, backing the `expenses` table.
 */
class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Dated in the future would land outside every period report.
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', 'string', Rule::in(Expense::CATEGORIES)],
            'payee' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'payment_method' => ['required', 'string', Rule::in(Expense::PAYMENT_METHODS)],
            'status' => ['required', 'string', Rule::in(Expense::STATUSES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expense_date.before_or_equal' => 'An expense cannot be dated in the future.',
        ];
    }
}
