<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The "Log Hours" form: one client, and a row of hours for each day worked.
 *
 * `therapist_id` is not accepted here; the controller forces it from the
 * acting user, the same pattern StoreBillingItemRequest follows for a
 * therapist's own bill. An aide only ever logs their own hours.
 */
class StoreTimesheetEntryRequest extends FormRequest
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
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.entry_date' => ['required', 'date'],
            // Hours are fractional: 0.25 and 1.5 are real quantities. A
            // column left blank is zero, not missing.
            'entries.*.hourly_respite_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'entries.*.community_support_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'entries.*.bda_direct_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'entries.*.bda_indirect_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'entries.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * A day with nothing in any column is not a day worked, and two rows for
     * the same date would silently overwrite each other on save.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, array<string, mixed>> $entries */
            $entries = $this->input('entries', []);
            $seen = [];

            foreach ($entries as $index => $entry) {
                if ($this->loggedHours($entry) <= 0) {
                    $validator->errors()->add(
                        "entries.{$index}.hourly_respite_hours",
                        'Enter at least one hour for this day.',
                    );
                }

                $date = (string) ($entry['entry_date'] ?? '');

                if ($date !== '' && in_array($date, $seen, true)) {
                    $validator->errors()->add(
                        "entries.{$index}.entry_date",
                        'This date is already on the form. Put the day\'s hours on one row.',
                    );
                }

                $seen[] = $date;
            }
        });
    }

    /**
     * Every column added together — a day with only respite hours is still a
     * day worth logging, even though those hours sit outside the total.
     *
     * @param  array<string, mixed>  $entry
     */
    private function loggedHours(array $entry): float
    {
        return (float) ($entry['hourly_respite_hours'] ?? 0)
            + (float) ($entry['community_support_hours'] ?? 0)
            + (float) ($entry['bda_direct_hours'] ?? 0)
            + (float) ($entry['bda_indirect_hours'] ?? 0);
    }
}
