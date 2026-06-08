<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use App\Notifications\AnnouncementPosted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AnnouncementListController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $user->update(['last_announcement_read_at' => now()]);

        $query = Announcement::with(['course', 'poster'])->active()->latest();

        // Students only see global + their course's announcements
        if ($user->isStudent()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('course_id')
                  ->orWhere('course_id', $user->course_id);
            });
        }

        // Search and course filter (non-student only)
        if (! $user->isStudent()) {
            $query
                ->when($request->filled('search'), fn ($q) =>
                    $q->where('title', 'like', '%' . $request->input('search') . '%')
                )
                ->when($request->filled('course'), function ($q) use ($request) {
                    $value = $request->input('course');
                    if ($value === 'global') {
                        $q->whereNull('course_id');
                    } else {
                        $q->where('course_id', $value);
                    }
                });
        }

        $announcements = $query->paginate(20);

        $courses = \App\Models\Course::orderBy('name')->pluck('name', 'id');

        return view('pages.announcements.index', compact('announcements', 'courses'));
    }

    public function create(): View
    {
        $courses = Course::orderBy('name')->pluck('name', 'id');

        return view('pages.announcements.create', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:100'],
            'message'    => ['required', 'string', 'max:500'],
            'course_id'  => ['nullable', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $validated['posted_by'] = $request->user()->id;

        $announcement = Announcement::create($validated);

        $this->notifyStudents($announcement);

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement posted successfully.');
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        $this->authorizeManagement($request->user(), $announcement);

        $courses = Course::orderBy('name')->pluck('name', 'id');

        return view('pages.announcements.edit', compact('announcement', 'courses'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeManagement($request->user(), $announcement);

        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:100'],
            'message'    => ['required', 'string', 'max:500'],
            'course_id'  => ['nullable', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $announcement->update($validated);

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeManagement($request->user(), $announcement);

        $announcement->delete();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Announcement deleted.');
    }

    /**
     * Admins can manage any announcement. Advisers can only manage their own.
     */
    private function authorizeManagement(User $user, Announcement $announcement): void
    {
        if (! $user->isAdmin() && $announcement->posted_by !== $user->id) {
            abort(403);
        }
    }

    /**
     * Notify students about a new announcement.
     */
    private function notifyStudents(Announcement $announcement): void
    {
        $query = User::where('role', 'student');

        if ($announcement->course_id) {
            $query->where('course_id', $announcement->course_id);
        }

        Notification::send($query->get(), new AnnouncementPosted($announcement));
    }
}
