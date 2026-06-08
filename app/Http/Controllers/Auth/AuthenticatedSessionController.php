<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $courses = Course::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('auth.login', [
            'courses' => $courses,
            'mode' => 'login',
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Never use redirect()->intended() — it can carry a stale URL from
        // a previous user/role (e.g. admin logs out, teacher logs in, but
        // session('url.intended') still holds '/admin/courses' → 403).
        // Always land on the role-dispatched dashboard instead.
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
