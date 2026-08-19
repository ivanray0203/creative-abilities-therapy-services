<?php

namespace App\Http\Requests;

use App\Models\Program;
use App\Models\ProgramRegistration;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Public program sign-up. Open to guests, like the intake and career forms.
 */
class StoreProgramRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'participant_first_name' => ['required', 'string', 'max:255'],
            'participant_last_name' => ['required', 'string', 'max:255'],
            'participant_date_of_birth' => ['nullable', 'date', 'before:today'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_email' => ['required', 'email', 'max:255'],
            'parent_phone' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Capacity and the closing date are checked here as well as on the page,
     * because the page's copy was rendered when it loaded — a place can be
     * taken while the form sits open.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->program()->isOpenForRegistration()) {
                    $validator->errors()->add(
                        'program',
                        'This program is no longer accepting registrations.',
                    );
                }
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['parent_email', 'program'])) {
                    return;
                }

                $alreadyRegistered = ProgramRegistration::query()
                    ->where('program_id', $this->program()->id)
                    ->where('parent_email', $this->string('parent_email'))
                    ->where('participant_first_name', $this->string('participant_first_name'))
                    ->where('participant_last_name', $this->string('participant_last_name'))
                    ->whereNot('status', 'cancelled')
                    ->exists();

                if ($alreadyRegistered) {
                    $validator->errors()->add(
                        'participant_first_name',
                        'This child is already registered for this program.',
                    );
                }
            },
        ];
    }

    public function program(): Program
    {
        $program = $this->route('program');

        abort_unless($program instanceof Program, 404);

        return $program;
    }
}
