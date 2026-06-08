<?php

namespace App\Http\Controllers;

use App\Models\DefenseSchedule;
use App\Models\User;
use App\Notifications\DefenseScheduled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ScheduleController extends Controller
{
    /**
     * Return schedule events as JSON for the calendar.
     * Scoped: students see global + their course. Admin/Adviser see all.
     */
    public function events(Request $request): JsonResponse
    {
        $user = $request->user();

        $schedules = DefenseSchedule::with(['course', 'creator'])
            ->active()
            ->forUser($user)
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

        return response()->json($events);
    }

    public function store(Request $request): RedirectResponse
    {
        $minDate = now()->addDays(3)->format('Y-m-d');
        $maxDate = now()->addMonths(3)->format('Y-m-d');

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:191'],
            'description'    => ['nullable', 'string', 'max:120'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:' . $minDate, 'before_or_equal:' . $maxDate],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if (!$value || !$request->input('start_time')) {
                        return;
                    }
                    $startTs = strtotime($request->input('start_time'));
                    $endTs   = strtotime($value);
                    if ($endTs !== false && $startTs !== false && ($endTs - $startTs) > 43200) {
                        $fail('The defense duration cannot exceed 12 hours.');
                    }
                },
            ],
            'room'           => ['required', 'string', 'max:100'],
            'course_id'      => ['nullable', 'exists:courses,id'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $schedule = DefenseSchedule::create($validated);

        // Notify relevant students
        $this->notifyStudents($schedule);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Defense schedule created successfully.');
    }

    public function update(Request $request, DefenseSchedule $schedule): RedirectResponse
    {
        $minDate = now()->addDays(3)->format('Y-m-d');
        $maxDate = now()->addMonths(3)->format('Y-m-d');

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:191'],
            'description'    => ['nullable', 'string', 'max:120'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:' . $minDate, 'before_or_equal:' . $maxDate],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => [
                'nullable',
                'date_format:H:i',
                'after:start_time',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if (!$value || !$request->input('start_time')) {
                        return;
                    }
                    $startTs = strtotime($request->input('start_time'));
                    $endTs   = strtotime($value);
                    if ($endTs !== false && $startTs !== false && ($endTs - $startTs) > 43200) {
                        $fail('The defense duration cannot exceed 12 hours.');
                    }
                },
            ],
            'room'           => ['required', 'string', 'max:100'],
            'course_id'      => ['nullable', 'exists:courses,id'],
        ]);

        $schedule->update($validated);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Defense schedule updated successfully.');
    }

    public function destroy(DefenseSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Defense schedule deleted.');
    }

    /**
     * Notify students in scope: course-specific or all students if global.
     */
    private function notifyStudents(DefenseSchedule $schedule): void
    {
        $query = User::where('role', 'student')->where('status', 'active');

        if ($schedule->course_id) {
            $query->where('course_id', $schedule->course_id);
        }

        $students = $query->get();

        if ($students->isNotEmpty()) {
            Notification::send($students, new DefenseScheduled($schedule));
        }
    }
}
