<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Ported 1:1 from cats-frontend's src/pages/admin/CalendarPage.tsx.
     * The reference never wires this to a real endpoint either (its
     * fetchSessions() has the axios call commented out and always falls
     * back to dummySessions), so the page keeps its own mock session data.
     */
    public function index(): Response
    {
        return Inertia::render('admin/calendar');
    }
}
