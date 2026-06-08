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

class AnnouncementManageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $announcements = Announcement::with(['course', 'poster'])
            ->active()
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
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $archivedAnnouncements = Announcement::with(['course', 'poster'])
            ->expired()
            ->latest()
            ->take(100)
            ->get();

        $courses = Course::orderBy('name')->pluck('name', 'id');

        [$filterRoute, $storeRoute, $manageBaseUrl] = $this->resolveRoutes($user);

        return view('pages.announcements.manage', [
            'announcements'          => $announcements,
            'archivedAnnouncements'  => $archivedAnnouncements,
            'courses'                => $courses,
            'filterRoute'            => $filterRoute,
            'storeRoute'             => $storeRoute,
            'manageBaseUrl'          => $manageBaseUrl,
            'ownerId'                => $user->isAdmin() ? null : $user->id,
            'subtitle'               => $user->isAdmin()
                                            ? 'Manage announcements for students'
                                            : 'Manage your announcements for students',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:100'],
            'message'    => ['required', 'string', 'max:500'],
            'course_id'  => ['nullable', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date', 'after:today', 'before_or_equal:' . now()->addMonths(3)->format('Y-m-d')],
        ]);

        $validated['posted_by'] = $request->user()->id;

        $announcement = Announcement::create($validated);

        $this->notifyStudents($announcement);

        [, , , $indexRoute] = $this->resolveRoutes($request->user());

        return redirect()->route($indexRoute)->with('success', 'Announcement posted successfully.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOwnership($request->user(), $announcement);

        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:100'],
            'message'    => ['required', 'string', 'max:500'],
            'course_id'  => ['nullable', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addMonths(3)->format('Y-m-d')],
        ]);

        $announcement->update($validated);

        [, , , $indexRoute] = $this->resolveRoutes($request->user());

        return redirect()->route($indexRoute)->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorizeOwnership($request->user(), $announcement);

        $announcement->delete();

        [, , , $indexRoute] = $this->resolveRoutes($request->user());

        return redirect()->route($indexRoute)->with('success', 'Announcement deleted.');
    }

    /**
     * Admins can manage any announcement. Advisers can only manage their own.
     */
    private function authorizeOwnership(User $user, Announcement $announcement): void
    {
        if (! $user->isAdmin() && $announcement->posted_by !== $user->id) {
            abort(403);
        }
    }

    /**
     * Returns [filterRoute, storeRoute, manageBaseUrl, indexRoute] for the current user's role.
     */
    private function resolveRoutes(User $user): array
    {
        $prefix = $user->isAdmin() ? 'admin' : 'adviser';

        return [
            "{$prefix}.announcements.index",
            "{$prefix}.announcements.store",
            url("{$prefix}/announcements"),
            "{$prefix}.announcements.index",
        ];
    }

    private function notifyStudents(Announcement $announcement): void
    {
        $query = User::where('role', 'student');

        if ($announcement->course_id) {
            $query->where('course_id', $announcement->course_id);
        }

        Notification::send($query->get(), new AnnouncementPosted($announcement));
    }
}
