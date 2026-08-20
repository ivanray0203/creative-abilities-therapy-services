<?php

namespace App\Http\Requests\Admin;

use App\Models\Program;

/**
 * Same field set as StoreProgramRequest — the only difference is that the
 * program being edited is exempt from its own slug uniqueness check.
 */
class UpdateProgramRequest extends StoreProgramRequest
{
    protected function programId(): ?int
    {
        $program = $this->route('program');

        return $program instanceof Program ? $program->id : null;
    }
}
