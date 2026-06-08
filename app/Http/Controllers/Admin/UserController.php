<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        // Main table: active users only, filterable by search + role
        $users = User::with('course')
            ->where('status', 'active')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('last_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('email', 'like', '%' . $request->input('search') . '%');
            }))
            ->latest()
            ->paginate(6)
            ->withQueryString();

        // Pending section: separate, not affected by filters
        $pendingUsers = User::where('status', 'pending')
            ->with('course')
            ->latest()
            ->get();

        $courses = Course::orderBy('code')->get();

        // Adviser paper assignments: keyed by adviser_id for the modal "Papers" tab
        $adviserPapersMap = ResearchPaper::with(['course'])
            ->whereNotNull('adviser_id')
            ->get()
            ->groupBy('adviser_id')
            ->map(fn ($papers) => $papers->map(fn ($p) => [
                'id'     => $p->id,
                'title'  => $p->title,
                'status' => $p->status,
                'course' => $p->course?->displayCode() ?? $p->course?->name ?? 'N/A',
            ]));

        return view('pages.admin.users.index', compact('users', 'pendingUsers', 'courses', 'adviserPapersMap'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:191'],
            'last_name'  => ['required', 'string', 'max:191'],
            'email'      => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'role'       => ['required', 'in:admin,adviser,student'],
            'course_id'  => ['nullable', 'required_if:role,student', 'exists:courses,id'],
            'password'   => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'status'     => 'active',
            'is_adviser' => false,
            'course_id'  => $validated['course_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'  => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'max:25'],
            'last_name'   => ['required', 'string', 'regex:/^[A-Za-z\s]+$/', 'max:25'],
            'email'       => ['required', 'email', 'max:255'],
            'role'        => ['required', 'in:admin,adviser,student'],
            'course_id'   => ['nullable', 'integer', 'exists:courses,id'],
            'is_adviser'  => ['nullable'],
        ]);

        // Role transition guard: students can never be promoted
        if ($user->role === 'student' && $validated['role'] !== 'student') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Student accounts cannot be promoted to another role.');
        }

        // Role transition guard: advisers can only become admin (not student)
        if ($user->role === 'adviser' && $validated['role'] === 'student') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Adviser accounts cannot be demoted to student.');
        }

        // Role transition guard: admins can only become adviser (not student)
        if ($user->role === 'admin' && $validated['role'] === 'student') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Admin accounts cannot be demoted to student.');
        }

        $validated['is_adviser'] = $request->has('is_adviser') && $validated['role'] === 'admin';

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->status === 'pending', 422, 'User is not pending approval.');

        $user->update(['status' => 'active']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', $user->first_name . ' ' . $user->last_name . '\'s account has been approved.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->status === 'pending', 422, 'Only pending accounts can be rejected.');

        // Prevent deletion if the user has submitted any research papers.
        if ($user->submittedPapers()->exists()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'This account cannot be deleted because it has research papers attached.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Account request rejected and removed.');
    }
}
