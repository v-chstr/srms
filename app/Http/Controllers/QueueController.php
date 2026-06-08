<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Queue;
use App\Models\User;
use App\Notifications\QueueTurnNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::orderBy('code')->get();

        $queues = Queue::with(['course', 'creator', 'groups'])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($request->filled('status'),    fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->get();

        return view('pages.queue.index', compact('queues', 'courses'));
    }

    public function create(): View
    {
        $courses = Course::orderBy('code')->get();

        return view('pages.queue.create', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:150'],
            'course_id' => ['required', 'exists:courses,id'],
            'groups'    => ['required', 'json'],
        ]);

        $groups = json_decode($validated['groups'], true);

        if (!is_array($groups) || count($groups) === 0) {
            return back()->withErrors(['groups' => 'At least one group is required.'])->withInput();
        }

        // Collect all valid student IDs for this course (server-side lock)
        $courseStudentIds = User::where('course_id', $validated['course_id'])
            ->where('role', 'student')
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        foreach ($groups as $index => $group) {
            $members = $group['members'] ?? [];
            $count   = count($members);

            if ($count < 1 || $count > 3) {
                return back()->withErrors(['groups' => "Group " . ($index + 1) . " must have 1 to 3 members."])->withInput();
            }

            foreach ($members as $memberId) {
                if (!in_array((int) $memberId, $courseStudentIds, true)) {
                    return back()->withErrors(['groups' => 'All students must belong to the selected course.'])->withInput();
                }
            }
        }

        DB::transaction(function () use ($validated, $groups) {
            $queue = Queue::create([
                'title'            => $validated['title'],
                'course_id'        => $validated['course_id'],
                'created_by'       => auth()->id(),
                'status'           => 'pending',
                'current_position' => 0,
            ]);

            foreach ($groups as $index => $group) {
                $queueGroup = $queue->groups()->create(['position' => $index + 1]);

                foreach ($group['members'] as $userId) {
                    $queueGroup->members()->create(['user_id' => (int) $userId]);
                }
            }
        });

        return redirect()->route('queue.index')->with('success', 'Queue created successfully.');
    }

    public function show(Queue $queue): View
    {
        $queue->load(['course', 'creator', 'groups.members.user']);

        return view('pages.queue.show', compact('queue'));
    }

    public function next(Queue $queue): RedirectResponse
    {
        if ($queue->isCompleted()) {
            return back()->with('error', 'This queue is already completed.');
        }

        $totalGroups = $queue->groups()->count();

        if ($queue->isPending()) {
            $nextPosition = 1;
        } else {
            $nextPosition = $queue->current_position + 1;
        }

        if ($nextPosition > $totalGroups) {
            $queue->update(['status' => 'completed']);
            return back()->with('info', 'All groups have been called. Queue is now complete.');
        }

        $queue->update([
            'status'           => 'active',
            'current_position' => $nextPosition,
        ]);

        // Notify students in the new current group
        $currentGroup = $queue->groups()->with('members.user')->where('position', $nextPosition)->first();

        if ($currentGroup) {
            foreach ($currentGroup->members as $member) {
                $member->user?->notify(new QueueTurnNotification($queue, $currentGroup));
            }
        }

        return back()->with('success', "Group {$nextPosition} of {$totalGroups} has been called!");
    }

    public function destroy(Queue $queue): RedirectResponse
    {
        $queue->delete();

        return redirect()->route('queue.index')->with('success', 'Queue deleted.');
    }

    /**
     * AJAX: return active students for a given course (used by the create form).
     */
    public function courseStudents(Course $course): JsonResponse
    {
        $students = User::where('course_id', $course->id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'first_name' => $u->first_name,
                'last_name'  => $u->last_name,
                'full_name'  => $u->last_name . ', ' . $u->first_name,
            ]);

        return response()->json($students);
    }
}
