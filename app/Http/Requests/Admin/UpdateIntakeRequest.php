<?php

namespace App\Http\Requests\Admin;

use App\Models\Intake;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Admin "Edit Intake" form. Identical field set to the create form except
 * the primary parent email is locked once the intake already has one
 * (matching the reference's disabled email input), so its value is dropped
 * server-side rather than trusted from the client.
 */
class UpdateIntakeRequest extends StoreIntakeRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        if ($this->primaryParentEmailIsLocked()) {
            $rules['primary_parent_email'] = ['nullable', 'email', 'max:255'];
        }

        return $rules;
    }

    public function primaryParentEmailIsLocked(): bool
    {
        $intake = $this->route('intake');

        return $intake instanceof Intake && filled($intake->primary_parent_email);
    }
}
