<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResearchRequest;
use App\Http\Requests\UpdateResearchRequest;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Services\ResearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResearchController extends Controller
{
    public function __construct(private ResearchService $researchService) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // Mark research_reviewed notifications as read when student opens this page
        $user->unreadNotifications()
            ->where('data->type', 'research_reviewed')
            ->get()
            ->each->markAsRead();

        $papers = ResearchPaper::where('submitted_by', $user->id)
            ->with(['course', 'adviser', 'authors', 'reviews.reviewer'])
            ->latest()
            ->paginate(10);

        // Merge config defaults with most-used keywords from approved papers
        $keywordSuggestions = $this->buildKeywordSuggestions();

        $advisers = User::where('role', 'adviser')
            ->orWhere(fn ($q) => $q->where('role', 'admin')->where('is_adviser', true))
            ->orderBy('last_name')
            ->get();

        return view('pages.student.research.index', compact('papers', 'keywordSuggestions', 'advisers'));
    }

    public function store(StoreResearchRequest $request): RedirectResponse
    {
        $this->researchService->store($request->validated(), $request->user());

        return redirect()
            ->route('student.research.index')
            ->with('success', 'Research submitted successfully.');
    }

    public function update(UpdateResearchRequest $request, int $id): RedirectResponse
    {
        $paper = ResearchPaper::where('submitted_by', $request->user()->id)
            ->findOrFail($id);

        abort_if($paper->status === 'approved', 403, 'Approved papers cannot be edited.');

        $this->researchService->update($request->validated(), $paper);

        return redirect()
            ->route('student.research.index')
            ->with('success', 'Research updated successfully.');
    }

    public function download(Request $request, int $id)
    {
        $paper = ResearchPaper::where('submitted_by', $request->user()->id)
            ->findOrFail($id);

        return $this->researchService->download($paper);
    }

    public function preview(Request $request, int $id)
    {
        $paper = ResearchPaper::where('submitted_by', $request->user()->id)
            ->findOrFail($id);

        return $this->researchService->serveInline($paper);
    }

    public function annotate(Request $request, int $id): View
    {
        $paper = ResearchPaper::where('submitted_by', $request->user()->id)
            ->with(['submitter', 'adviser', 'annotations'])
            ->findOrFail($id);

        abort_unless($paper->status === 'revision' && $this->isPdf($paper), 404);

        $pdfUrl = route('student.research.preview', $paper->id);

        return view('pages.student.research.annotate', compact('paper', 'pdfUrl'));
    }

    public function annotations(Request $request, int $id): JsonResponse
    {
        $paper = ResearchPaper::where('submitted_by', $request->user()->id)
            ->findOrFail($id);

        abort_unless($paper->status === 'revision' && $this->isPdf($paper), 404);

        return response()->json($paper->annotations()->get());
    }

    /**
     * Build keyword suggestions by merging config defaults with popular
     * keywords from existing approved papers. Popular keywords surface first,
     * making suggestions smarter as more papers are submitted.
     */
    private function buildKeywordSuggestions(): array
    {
        $defaults = config('srms.keyword_suggestions', []);

        // Pull the most-used keywords from approved papers
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

        // Merge: popular first, then defaults that aren't already listed
        return collect($popularKeywords)
            ->merge($defaults)
            ->unique()
            ->take(25)
            ->values()
            ->toArray();
    }

    private function isPdf(ResearchPaper $paper): bool
    {
        $filename = strtolower($paper->original_filename ?? $paper->file_path ?? '');

        return str_ends_with($filename, '.pdf');
    }
}
