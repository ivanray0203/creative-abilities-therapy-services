<?php

namespace App\Http\Requests\Admin;

use App\Models\ClientService;
use App\Models\ServiceContract;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Admin "Issue Contract" form: the hours a therapist is authorized to deliver
 * against one availed service, and the window they have to deliver them in.
 *
 * Nothing here is a therapist's decision. A therapist consumes the pool; only
 * an admin may size it.
 */
class StoreServiceContractRequest extends FormRequest
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
            // A quarter of an hour is the smallest slice worth authorizing;
            // the ceiling is a typo guard, not a policy.
            'allotted_hours' => ['required', 'numeric', 'min:0.25', 'max:2000', $this->coversHoursAlreadyDrawn()],
            // Which funder pays for these hours. Optional: a contract can be
            // issued before the funding is confirmed.
            'funding_code' => ['nullable', 'string', Rule::in(ServiceContract::FUNDING_CODES)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start', $this->doesNotOverlapAnotherContract()],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period_end.after_or_equal' => 'A contract cannot end before it starts.',
        ];
    }

    /**
     * The availed service the contract is being issued against, resolved from
     * the nested route.
     */
    public function clientService(): ClientService
    {
        $service = $this->route('clientService');

        abort_unless($service instanceof ClientService, 404);

        return $service;
    }

    /** The contract being edited, or null while issuing a new one. */
    public function editedContract(): ?ServiceContract
    {
        $contract = $this->route('contract');

        return $contract instanceof ServiceContract ? $contract : null;
    }

    /**
     * Two live contracts covering the same day would leave the booking gate
     * with a choice to make and no rule for making it. Overlaps are rejected
     * here rather than by a unique key so the message can name the clash.
     */
    private function doesNotOverlapAnotherContract(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $start = $this->periodBoundary('period_start');
            $end = $this->periodBoundary('period_end');

            if ($start === null || $end === null) {
                return;
            }

            $clash = $this->clientService()->contracts()
                ->where('status', '!=', ServiceContract::STATUS_CANCELLED)
                ->when(
                    $this->editedContract() !== null,
                    fn (Builder $query) => $query->whereKeyNot($this->editedContract()->id),
                )
                // Touching periods are fine; a shared day is not.
                ->whereDate('period_start', '<=', $end)
                ->whereDate('period_end', '>=', $start)
                ->first();

            if ($clash !== null) {
                $fail("This overlaps contract {$clash->contract_number}, which runs {$clash->periodLabel()}. Cancel it first, or choose a period after it ends.");
            }
        };
    }

    /**
     * An allotment cannot be cut below the hours sessions have already taken.
     * Doing so would drive the balance negative, and the gate would report
     * the contract exhausted for work that was genuinely delivered.
     */
    private function coversHoursAlreadyDrawn(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $contract = $this->editedContract();

            if ($contract === null) {
                return;
            }

            $used = $contract->usedHours();

            if ((float) $value < $used) {
                $fail("Sessions have already drawn {$used} hours from this contract, so it cannot be cut to {$value}.");
            }
        };
    }

    /**
     * A period edge as a plain date, ignoring any time the browser sends.
     *
     * Not named `date()`: `Illuminate\Http\Request` already has one, and PHP
     * will not let a subclass narrow it to private.
     */
    private function periodBoundary(string $key): ?Carbon
    {
        $value = $this->input($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }
}
