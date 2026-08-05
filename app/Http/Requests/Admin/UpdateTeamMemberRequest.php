<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Same field set as StoreTeamMemberRequest minus the paired-User creation
 * fields — editing a team member never changes their login email here.
 */
class UpdateTeamMemberRequest extends StoreTeamMemberRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->teamMemberRules();
    }
}
