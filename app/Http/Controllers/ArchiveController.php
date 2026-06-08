<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ResearchPaper;
use App\Services\CitationService;
use App\Services\ResearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    public function __construct(
        private CitationService $citationService,
        private ResearchService $researchService,
    ) {}

    /**
     * Public archive — searchable list of approved research papers.
     */
    public function index(Request $request): View
    {
        $query = ResearchPaper::approved()
            ->with(['course', 'authors', 'adviser']);

        // Keyword search (title, abstract, or JSON keywords column)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('abstract', 'like', "%{$search}%")
                  ->orWhereRaw('JSON_SEARCH(LOWER(keywords), \'one\', ?) IS NOT NULL', [strtolower($search)]);
            });
        }

        // Course filter
        if ($courseId = $request->input('course_id')) {
            $query->forCourse((int) $courseId);
        }

        // Year filter
        if ($year = $request->input('year')) {
            $query->where('published_year', $year);
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->oldest(),
            'title'  => $query->orderBy('title'),
            default  => $query->latest(),
        };

        $papers = $query->paginate(8)->withQueryString();
        $courses = Course::orderBy('name')->pluck('name', 'id');

        // Collect distinct years for filter dropdown
        $years = ResearchPaper::approved()
            ->whereNotNull('published_year')
            ->selectRaw('DISTINCT published_year')
            ->orderByDesc('published_year')
            ->pluck('published_year');

        return view('pages.archive.index', compact('papers', 'courses', 'years'));
    }

    /**
     * Single paper detail in the archive with citation generator.
     */
    public function show(int $id): View
    {
        $paper = ResearchPaper::approved()
            ->with(['course', 'authors', 'adviser', 'reviews.reviewer'])
            ->findOrFail($id);

        $citations = $this->citationService->all($paper);

        return view('pages.archive.show', compact('paper', 'citations'));
    }

    /**
     * Download an approved paper's PDF from the archive.
     */
    public function download(int $id): StreamedResponse
    {
        $paper = ResearchPaper::approved()->findOrFail($id);

        return $this->researchService->download($paper);
    }

    /**
     * Serve an approved paper inline (for preview / iframe thumbnail fallback).
     * Public route — no auth required.
     */
    public function preview(int $id): StreamedResponse
    {
        $paper = ResearchPaper::approved()->findOrFail($id);

        return $this->researchService->serveInline($paper);
    }
}
