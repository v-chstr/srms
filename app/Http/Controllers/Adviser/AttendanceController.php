<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\AttendanceGroup;
use App\Models\AttendanceSection;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user    = $request->user();
        $courses = Course::orderBy('code')->get();

        $sections = AttendanceSection::with(['course', 'creator', 'groups', 'sharedAdvisers'])
            ->forAdviser($user->id)
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->latest()
            ->get();

        return view('pages.adviser.attendance.index', compact('sections', 'courses'));
    }

    public function create(): View
    {
        $courses = Course::orderBy('code')->get();

        return view('pages.adviser.attendance.create', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:150'],
            'course_id'  => ['required', 'exists:courses,id'],
            'groups'     => ['required', 'json'],
            'advisers'   => ['nullable', 'json'],
        ]);

        $groups   = json_decode($validated['groups'], true);
        $advisers = json_decode($validated['advisers'] ?? '[]', true);

        if (! is_array($groups) || count($groups) === 0) {
            return back()->withErrors(['groups' => 'At least one group is required.'])->withInput();
        }

        if (count($groups) > 50) {
            return back()->withErrors(['groups' => 'Too many groups.'])->withInput();
        }

        // Server-side: each group must have 1–3 members who are active students in this course
        $courseStudentIds = User::where('course_id', $validated['course_id'])
            ->where('role', 'student')
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        foreach ($groups as $index => $group) {
            $members = $group['members'] ?? [];
            $count   = count($members);

            if ($count < 1 || $count > 3) {
                return back()->withErrors(['groups' => 'Group ' . ($index + 1) . ' must have 1 to 3 members.'])->withInput();
            }

            foreach ($members as $memberId) {
                if (! in_array((int) $memberId, $courseStudentIds, true)) {
                    return back()->withErrors(['groups' => 'All students must belong to the selected course.'])->withInput();
                }
            }
        }

        // Validate shared advisers (max 5, must be adviser-capable, exclude self)
        $adviserIds = [];
        if (! empty($advisers)) {
            if (count($advisers) > 5) {
                return back()->withErrors(['advisers' => 'You can share with at most 5 advisers.'])->withInput();
            }

            $adviserIds = User::whereIn('id', $advisers)
                ->where(fn ($q) => $q
                    ->where('role', 'adviser')
                    ->orWhere(fn ($q2) => $q2->where('role', 'admin')->where('is_adviser', true))
                )
                ->where('id', '!=', $request->user()->id)
                ->pluck('id')
                ->all();
        }

        $section = DB::transaction(function () use ($validated, $groups, $adviserIds, $request) {
            $section = AttendanceSection::create([
                'title'      => $validated['title'],
                'course_id'  => $validated['course_id'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($groups as $index => $group) {
                $attendanceGroup = $section->groups()->create(['position' => $index + 1]);

                foreach ($group['members'] as $userId) {
                    $attendanceGroup->members()->attach((int) $userId);
                }

                // Populate default timeline rows from template
                $attendanceGroup->createDefaultTimelineRows();
            }

            if (! empty($adviserIds)) {
                $section->sharedAdvisers()->attach($adviserIds);
            }

            return $section;
        });

        return redirect()
            ->route('adviser.attendance.show', $section)
            ->with('success', 'Attendance section created successfully.');
    }

    public function show(AttendanceSection $section): View
    {
        $user = auth()->user();

        if (! $section->isAccessibleBy($user->id)) {
            abort(403);
        }

        $section->load(['course', 'creator', 'sharedAdvisers', 'groups.members', 'groups.rows.recorder', 'groups.rows.studentAttendances.student']);

        // Fetch all active students in the course to allow additions to groups
        $courseStudents = User::where('course_id', $section->course_id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('pages.adviser.attendance.show', compact('section', 'courseStudents'));
    }

    public function destroy(AttendanceSection $section): RedirectResponse
    {
        $user = auth()->user();

        if ((int) $section->created_by !== (int) $user->id) {
            abort(403);
        }

        $section->delete();

        return redirect()
            ->route('adviser.attendance.index')
            ->with('success', 'Attendance section deleted.');
    }

    public function addMember(Request $request, AttendanceGroup $group): RedirectResponse
    {
        $user = $request->user();

        $group->load('section.sharedAdvisers');

        if (! $group->section->isAccessibleBy($user->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
        ]);

        $studentId = (int) $validated['student_id'];

        $student = User::where('id', $studentId)
            ->where('course_id', $group->section->course_id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->first();

        if (! $student) {
            return back()->with('error', 'Invalid student for this course.');
        }

        if ($group->members()->where('user_id', $studentId)->exists()) {
            return back()->with('error', 'Student is already in this group.');
        }

        if ($group->members()->count() >= 3) {
            return back()->with('error', 'This group already has the maximum of 3 students.');
        }

        $alreadyAssigned = AttendanceGroup::where('section_id', $group->section_id)
            ->where('id', '!=', $group->id)
            ->whereHas('members', fn ($query) => $query->where('users.id', $studentId))
            ->exists();

        if ($alreadyAssigned) {
            return back()->with('error', 'Student is already assigned to another group in this section.');
        }

        $group->members()->attach($studentId);
        $group->load('members', 'rows.studentAttendances');

        foreach ($group->rows as $row) {
            $row->syncStudentAttendanceForGroup(
                $group,
                $row->studentAttendances->pluck('status', 'user_id')->all()
            );
        }

        return back()
            ->with('success', 'Student added to group successfully.')
            ->with('active_group_id', $group->id);
    }
}
