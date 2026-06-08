<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\Annotation;
use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnotationController extends Controller
{
    public function index(Request $request, ResearchPaper $paper): JsonResponse
    {
        $this->authorizePaper($request->user(), $paper);

        return response()->json($paper->annotations()->get());
    }

    public function update(Request $request, ResearchPaper $paper, Annotation $annotation): JsonResponse
    {
        $this->authorizePaper($request->user(), $paper);
        abort_unless($annotation->research_paper_id === $paper->id, 404);
        abort_unless($annotation->adviser_id === $request->user()->id, 403);
        abort_unless($annotation->type === 'note', 422, 'Only notes can be repositioned.');

        $validated = $request->validate([
            'x' => ['required', 'numeric', 'between:0,100'],
            'y' => ['required', 'numeric', 'between:0,100'],
        ]);

        $annotation->forceFill($validated)->save();

        return response()->json($annotation->fresh());
    }

    public function store(Request $request, ResearchPaper $paper): JsonResponse
    {
        $this->authorizePaper($request->user(), $paper);

        abort_unless($this->isPdf($paper), 422, 'Only PDF submissions can be annotated.');

        $validated = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:highlight,text,note'],
            'x' => ['required', 'numeric', 'between:0,100'],
            'y' => ['required', 'numeric', 'between:0,100'],
            'w' => ['nullable', 'numeric', 'between:0,100'],
            'h' => ['nullable', 'numeric', 'between:0,100'],
            'content' => ['nullable', 'string', 'max:5000'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $annotation = Annotation::create([
            ...$validated,
            'research_paper_id' => $paper->id,
            'adviser_id' => $request->user()->id,
            'color' => $validated['color'] ?? '#facc15',
            'created_at' => now(),
        ]);

        return response()->json($annotation, 201);
    }

    public function destroy(Request $request, ResearchPaper $paper, Annotation $annotation): JsonResponse
    {
        $this->authorizePaper($request->user(), $paper);
        abort_unless($annotation->research_paper_id === $paper->id, 404);
        abort_unless($annotation->adviser_id === $request->user()->id, 403);

        $annotation->delete();

        return response()->json(['deleted' => true]);
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
