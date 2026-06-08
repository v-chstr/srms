<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Find the group this student belongs to, with all timeline rows and attendance records
        $group = \App\Models\AttendanceGroup::whereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->whereHas('section')
            ->with([
                'section.course',
                'section.creator',
                'section.sharedAdvisers',
                'members',
                'rows.recorder',
                'rows.studentAttendances.student',
            ])
            ->first();

        $statusTotals = [
            AttendanceRecord::STATUS_PRESENT => 0,
            AttendanceRecord::STATUS_ABSENT  => 0,
            'not_recorded'                   => 0,
        ];

        if ($group) {
            $userRecords = AttendanceRecord::where('user_id', $user->id)
                ->whereIn('attendance_row_id', $group->rows->pluck('id'))
                ->get();

            $statusTotals = [
                AttendanceRecord::STATUS_PRESENT => $userRecords->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
                AttendanceRecord::STATUS_ABSENT  => $userRecords->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
                'not_recorded'                   => $userRecords->whereNull('status')->count(),
            ];
        }

        return view('pages.student.attendance.index', compact('group', 'statusTotals'));
    }
}
