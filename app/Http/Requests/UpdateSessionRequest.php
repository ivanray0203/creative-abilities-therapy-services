<?php

namespace App\Http\Requests;

/**
 * Same field set as StoreSessionRequest — reschedules reuse the same
 * date/time/duration → scheduled_start/scheduled_end computation.
 */
class UpdateSessionRequest extends StoreSessionRequest
{
    //
}
