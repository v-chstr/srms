<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @deprecated Defense scheduling has moved to ScheduleController.
 * This controller is kept temporarily for route compatibility.
 */
class DefenseScheduleController extends Controller
{
    public function store(Request $request, int $paper): RedirectResponse
    {
        // Redirect to dashboard — defense scheduling is now course-scoped via ScheduleController
        return redirect()->route('dashboard')
            ->with('info', 'Defense scheduling has moved to the dashboard calendar.');
    }
}
