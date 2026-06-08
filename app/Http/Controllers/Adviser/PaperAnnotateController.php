<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Services\ResearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaperAnnotateController extends Controller
{
    public function __construct(private ResearchService $researchService) {}

    public function show(Request $request, ResearchPaper $paper): View
    {
        $this->authorizePaper($request->user(), $paper);
        abort_unless($this->isPdf($paper), 404);

        $paper->load(['submitter', 'adviser', 'annotations']);
        $pdfUrl = route('adviser.reviews.preview', $paper->id);

        return view('pages.adviser.reviews.annotate', compact('paper', 'pdfUrl'));
    }

    public function submit(Request $request, ResearchPaper $paper): RedirectResponse
    {
        $this->authorizePaper($request->user(), $paper);

        $validated = $request->validate([
            'action' => ['required', 'in:send_back,approve'],
        ]);

        $this->researchService->submitReview($paper, [
            'comments' => $validated['action'] === 'approve'
                ? 'Approved through the annotated review viewer.'
                : 'See adviser annotations on the manuscript.',
            'decision' => $validated['action'] === 'approve' ? 'approved' : 'revision_required',
        ], $request->user());

        return redirect()
            ->route('adviser.reviews.index')
            ->with('success', $validated['action'] === 'approve'
                ? 'Paper approved successfully.'
                : 'Paper returned for revision with annotations.');
    }

    private function authorizePaper(User $user, ResearchPaper $paper): void
    {
        abort_unless(
            $paper->adviser_id === $user->id || ($user->isAdmin() && $user->is_adviser),
            403
        );
    }

    private function isPdf(ResearchPaper $paper): bool
    {
        $filename = strtolower($paper->original_filename ?? $paper->file_path ?? '');

        return str_ends_with($filename, '.pdf');
    }
}
