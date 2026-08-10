<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Phase 18 — lets controllers call `$this->authorize()` so policies can
     * enforce access in code, rather than relying on which route group a
     * method happens to be wired into.
     */
    use AuthorizesRequests;
}
