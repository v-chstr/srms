<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $courses = Course::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('auth.login', [
            'courses' => $courses,
            'mode' => 'register',
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:191'],
            'last_name'  => ['required', 'string', 'max:191'],
            'email'      => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'is_student' => ['sometimes'],
            'course_id'  => ['nullable', 'required_if:is_student,1', 'exists:courses,id'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $isStudent = $request->boolean('is_student');

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $isStudent ? 'student' : 'adviser',
            'status'     => 'pending',
            'is_adviser' => false,
            'course_id'  => $isStudent ? $validated['course_id'] : null,
        ]);

        return redirect()->route('login')->with(
            'status',
            'Your account request has been submitted. An administrator will review it shortly.'
        );
    }
}
