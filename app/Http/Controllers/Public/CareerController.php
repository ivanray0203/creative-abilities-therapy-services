<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Career;
use Inertia\Inertia;
use Inertia\Response;

class CareerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/careers', [
            'careers' => Career::query()->where('is_active', true)->orderByDesc('due_date')->get(),
        ]);
    }

    public function show(Career $career): Response
    {
        return Inertia::render('public/career-detail', [
            'career' => $career,
        ]);
    }
}
