<?php

namespace App\Policies;

use App\Models\Timesheet;
use App\Models\User;
use App\Services\ClientContext;

/**
 * Who may read and sign an aide's time sheet.
 *
 * The form has two authors and one reader: the aide who logged the hours,
 * the parent who confirms them, and the admin who settles off the result.
 * Nobody else is party to it — a therapist on the same care team has no
 * business in another aide's sheet.
 */
class TimesheetPolicy
{
    public function __construct(private ClientContext $clientContext) {}

    /**
     * A parent may open any of their children's timesheets, not just the one
     * the portal switcher currently has selected.
     */
    public function view(User $user, Timesheet $timesheet): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTherapist()) {
            return $timesheet->therapist_id === $user->id;
        }

        return $user->isClient()
            && $this->clientContext->owns($user, $timesheet->client_id);
    }

    /**
     * Only the parent the form is about may sign it, and only once — a
     * signed timesheet is the record of what they confirmed, so it is not
     * re-signable.
     */
    public function sign(User $user, Timesheet $timesheet): bool
    {
        return $user->isClient()
            && $timesheet->parent_signature === null
            && $this->clientContext->owns($user, $timesheet->client_id);
    }

    public function delete(User $user, Timesheet $timesheet): bool
    {
        return $user->isAdmin();
    }
}
