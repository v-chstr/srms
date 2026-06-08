<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDirectUploadRequest;
use App\Models\Course;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Services\ResearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaperController extends Controller
{
    public function __construct(private ResearchService $researchService) {}

    public function index(Request $request): View
    {
        $papers = ResearchPaper::with(['course', 'submitter', 'adviser', 'authors', 'reviews.reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->latest()
            ->paginate(6);

        $courses = Course::orderBy('code')->get();

        $advisers = User::where('role', 'adviser')
            ->orWhere(fn ($q) => $q->where('role', 'admin')->where('is_adviser', true))
            ->orderBy('last_name')
            ->get();

        $popularKeywords = ResearchPaper::approved()
            ->whereNotNull('keywords')
            ->pluck('keywords')
            ->flatten()
            ->map(fn ($kw) => strtolower(trim($kw)))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(20)
            ->toArray();

        $keywordSuggestions = collect($popularKeywords)
            ->merge(config('srms.keyword_suggestions', []))
            ->unique()
            ->take(25)
            ->values()
            ->toArray();

        return view('pages.admin.papers.index', compact('papers', 'courses', 'keywordSuggestions', 'advisers'));
    }

    public function store(StoreDirectUploadRequest $request): RedirectResponse
    {
        $this->researchService->storeDirectUpload($request->validated(), $request->user());

        return redirect()->route('admin.papers.index')->with('success', 'Paper uploaded and approved successfully.');
    }

    public function download(int $paper)
    {
        $paper = ResearchPaper::findOrFail($paper);

        return $this->researchService->download($paper);
    }

    public function preview(int $paper)
    {
        $paper = ResearchPaper::findOrFail($paper);

        return $this->researchService->serveInline($paper);
    }

    public function destroy(int $paper): RedirectResponse
    {
        $paper = ResearchPaper::findOrFail($paper);
        $paper->delete();

        return redirect()->route('admin.papers.index')->with('success', 'Paper deleted.');
    }
}

