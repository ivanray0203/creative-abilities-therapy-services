<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class TherapistListController extends Controller
{
    /**
     * Active therapists, for assign-therapist dropdowns across the admin
     * app. Mirrors the reference's dedicated `GET /accounts/therapists/`
     * endpoint (kept separate from the full TeamMember CRUD in Phase 11).
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(
            User::query()
                ->where('role', 'therapist')
                ->where('is_active', true)
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email'])
        );
    }
}
