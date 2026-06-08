<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::withCount('researchPapers')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('code', 'like', '%' . $request->input('search') . '%')
                       ->orWhere('name', 'like', '%' . $request->input('search') . '%');
                });
            })
            ->orderBy('code')
            ->get();

        return view('pages.admin.courses.index', compact('courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:courses,code'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        Course::create($validated);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function update(Request $request, int $course): RedirectResponse
    {
        $course = Course::findOrFail($course);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:courses,code,' . $course->id],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $course->update($validated);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }
}

