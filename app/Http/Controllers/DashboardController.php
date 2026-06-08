<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\DefenseSchedule;
use App\Models\QueueGroup;
use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return match ($user->role) {
            'admin'   => $this->adminDashboard($user),
            'adviser' => $this->adviserDashboard($user),
            'student' => $this->studentDashboard($user),
            default   => abort(403, 'Unauthorized.'),
        };
    }

    private function adminDashboard(User $user): View
    {
        // Course research output breakdown
        $courseOutput = Course::withCount('researchPapers')
            ->orderByDesc('research_papers_count')
            ->get()
            ->map(fn ($c) => ['name' => $c->name, 'code' => $c->displayCode(), 'count' => $c->research_papers_count]);

        $recentPapers = ResearchPaper::with(['submitter', 'course'])
            ->latest()
            ->take(3)
            ->get();

        $pendingUsers = User::where('status', 'pending')
            ->with('course')
            ->latest()
            ->take(3)
            ->get();

        [$latestAnnouncement, $unseenAnnouncementCount] = $this->getAnnouncementData($user);

        [$scheduleEvents, $courses, $schedules] = $this->getScheduleData($user);

        return view('dashboard', [
            'role'                     => 'admin',
            'courseOutput'             => $courseOutput,
            'recentPapers'             => $recentPapers,
            'pendingUsers'             => $pendingUsers,
            'latestAnnouncement'       => $latestAnnouncement,
            'unseenAnnouncementCount'  => $unseenAnnouncementCount,
            'scheduleEvents'           => $scheduleEvents,
            'courses'                  => $courses,
            'schedules'                => $schedules,
        ]);
    }

    private function adviserDashboard(User $user): View
    {
        $recentPapers = ResearchPaper::with(['submitter', 'course'])
            ->where('adviser_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        [$latestAnnouncement, $unseenAnnouncementCount] = $this->getAnnouncementData($user);

        [$scheduleEvents, $courses, $schedules] = $this->getScheduleData($user);

        return view('dashboard', [
            'role'                     => 'adviser',
            'recentPapers'             => $recentPapers,
            'latestAnnouncement'       => $latestAnnouncement,
            'unseenAnnouncementCount'  => $unseenAnnouncementCount,
            'scheduleEvents'           => $scheduleEvents,
            'courses'                  => $courses,
            'schedules'                => $schedules,
        ]);
    }

    private function studentDashboard(User $user): View
    {
        $recentPapers = ResearchPaper::with(['adviser', 'course'])
            ->where('submitted_by', $user->id)
            ->latest()
            ->take(3)
            ->get();

        // Find if the student is assigned to an active or pending queue
        $activeQueueGroup = QueueGroup::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('queue', fn ($q) => $q->whereIn('status', ['active', 'pending']))
            ->with([
                'queue.course',
                'queue.groups',
                'members.user',
            ])
            ->first();

        // Fetch student's attendance stats
        $attendanceRecords = $user->attendanceRecords()->get();
        $attendanceStats = [
            'present'      => $attendanceRecords->where('status', \App\Models\AttendanceRecord::STATUS_PRESENT)->count(),
            'absent'       => $attendanceRecords->where('status', \App\Models\AttendanceRecord::STATUS_ABSENT)->count(),
            'not_recorded' => $attendanceRecords->whereNull('status')->count(),
        ];

        [$latestAnnouncement, $unseenAnnouncementCount] = $this->getAnnouncementData($user);

        [$scheduleEvents, $courses] = $this->getScheduleData($user);

        return view('dashboard', [
            'role'                     => 'student',
            'recentPapers'             => $recentPapers,
            'activeQueueGroup'         => $activeQueueGroup,
            'latestAnnouncement'       => $latestAnnouncement,
            'unseenAnnouncementCount'  => $unseenAnnouncementCount,
            'scheduleEvents'           => $scheduleEvents,
            'courses'                  => $courses,
            'attendanceStats'          => $attendanceStats,
        ]);
    }

    /**
     * Get the latest announcement and unseen count for the given user.
     *
     * @return array{0: ?\App\Models\Announcement, 1: int}
     */
    private function getAnnouncementData(User $user): array
    {
        $baseQuery = Announcement::with(['poster', 'course'])->active();

        // Students only see global + their course announcements
        if ($user->isStudent()) {
            $baseQuery->where(function ($q) use ($user) {
                $q->whereNull('course_id')
                  ->orWhere('course_id', $user->course_id);
            });
        }

        $latestAnnouncement = (clone $baseQuery)->latest()->first();

        $lastReadAt = $user->last_announcement_read_at;

        if ($lastReadAt) {
            $totalUnseen = (clone $baseQuery)->where('created_at', '>', $lastReadAt)->count();
        } else {
            $totalUnseen = (clone $baseQuery)->count();
        }

        // Subtract the one already shown on dashboard
        $unseenAnnouncementCount = max($totalUnseen - ($latestAnnouncement ? 1 : 0), 0);

        return [$latestAnnouncement, $unseenAnnouncementCount];
    }

    /**
     * Get defense schedule events as a JSON-ready array + course list for the calendar.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Database\Eloquent\Collection}
     */
    private function getScheduleData(User $user): array
    {
        $schedules = DefenseSchedule::with(['course', 'creator'])
            ->active()
            ->forUser($user)
            ->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->get();

        $events = $schedules->map(fn (DefenseSchedule $s) => [
            'id'    => $s->id,
            'title' => $s->title,
            'start' => $s->scheduled_date->format('Y-m-d') . 'T' . $s->start_time->format('H:i:s'),
            'end'   => $s->end_time
                ? $s->scheduled_date->format('Y-m-d') . 'T' . $s->end_time->format('H:i:s')
                : null,
            'extendedProps' => [
                'schedule_id'    => $s->id,
                'description'    => $s->description,
                'room'           => $s->room,
                'course_code'    => $s->course?->displayCode(),
                'course_name'    => $s->course?->name,
                'is_global'      => $s->isGlobal(),
                'creator_name'   => $s->creator ? $s->creator->first_name . ' ' . $s->creator->last_name : 'Unknown',
                'start_time'     => $s->start_time->format('g:i A'),
                'end_time'       => $s->end_time?->format('g:i A'),
                'scheduled_date' => $s->scheduled_date->format('M d, Y'),
            ],
            'classNames' => $s->isGlobal() ? ['fc-event-global'] : ['fc-event-course'],
        ]);

        $courses = Course::orderBy('code')->get();

        return [$events, $courses, $schedules];
    }
}
