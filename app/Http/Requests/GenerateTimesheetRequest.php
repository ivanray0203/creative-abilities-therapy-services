<?php

namespace App\Http\Requests;

use App\Rules\PngSignature;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The timesheets list's "Generate Timesheet" modal.
 *
 * An aide sheets one child at a time — the form is addressed to that
 * family's parent — so the client is named alongside the period. The aide
 * signs as they generate, so their signature is part of the request rather
 * than a later step.
 */
class GenerateTimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAide() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'date_start' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after_or_equal:date_start'],
            'signature' => ['required', 'string', 'max:2000000', new PngSignature],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date_start' => 'start date',
            'date_end' => 'end date',
        ];
    }
}
