<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceRowController extends Controller
{
    public function update(Request $request, AttendanceRow $row): RedirectResponse
    {
        $user = $request->user();

        $row->load('group.section.sharedAdvisers', 'group.members');

        if (! $row->group->section->isAccessibleBy($user->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'date'                => ['required', 'string', 'max:100'],
            'activities'          => ['nullable', 'string', 'max:2000'],
            'remarks'             => ['nullable', 'string', 'max:191'],
            'student_statuses'    => ['nullable', 'array'],
            'student_statuses.*'  => ['nullable', 'in:present,absent,late,excused'],
        ]);

        $validated['activities'] = AttendanceRow::normalizeActivities($validated['activities'] ?? null);
        $validated['recorded_by'] = $user->id;
        $validated['attendance'] = null;

        $row->update($validated);
        $row->syncStudentAttendanceForGroup($row->group, $validated['student_statuses'] ?? []);

        return redirect()
            ->route('adviser.attendance.show', $row->group->section_id)
            ->with('success', 'Row updated.')
            ->with('active_group_id', $row->group_id);
    }

}
