<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ResearchPaper;
use App\Services\ResearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private ResearchService $researchService) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // Mark research_submitted notifications as read when adviser opens the review queue
        $user->unreadNotifications()
            ->where('data->type', 'research_submitted')
            ->get()
            ->each->markAsRead();

        $courses = Course::orderBy('code')->get();

        // Auto-filter: default to adviser's own course if they have one and no filter is explicitly set
        $courseFilter = $request->input('course_id');
        $autoFiltered = false;

        if (! $request->has('course_id') && $user->course_id) {
            $courseFilter = $user->course_id;
            $autoFiltered = true;
        }

        $papers = ResearchPaper::with(['course', 'submitter', 'adviser', 'authors', 'reviews.reviewer'])
            ->where('adviser_id', $user->id)
            ->when($courseFilter, fn ($q) => $q->where('course_id', $courseFilter))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('pages.adviser.reviews.index', compact('papers', 'courses', 'courseFilter', 'autoFiltered'));
    }

    public function store(Request $request, int $paper): RedirectResponse
    {
        $researchPaper = ResearchPaper::findOrFail($paper);

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:5000'],
            'decision' => ['required', 'in:revision_required,approved'],
        ]);

        $this->researchService->submitReview($researchPaper, $validated, $request->user());

        return redirect()
            ->route('adviser.reviews.index')
            ->with('success', 'Review submitted successfully.');
    }

    public function download(Request $request, int $paper)
    {
        $paper = ResearchPaper::findOrFail($paper);

        return $this->researchService->download($paper);
    }

    public function preview(Request $request, int $paper)
    {
        $paper = ResearchPaper::findOrFail($paper);

        return $this->researchService->serveInline($paper);
    }
}

