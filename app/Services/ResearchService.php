<?php

namespace App\Services;

use App\Models\ResearchAuthor;
use App\Models\ResearchPaper;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ResearchReviewed;
use App\Notifications\ResearchSubmitted;
use Aws\Exception\AwsException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException as FlysystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResearchService
{
    /**
     * Store a new research paper submission.
     * Saves the manuscript file to the configured disk and creates the DB record.
     */
    public function store(array $validated, User $submitter): ResearchPaper
    {
        abort_if(
            is_null($submitter->course_id),
            422,
            'Your account has no course assigned. Please contact an administrator.'
        );

        $disk = config('filesystems.default');
        $originalFilename = $validated['manuscript']->getClientOriginalName();

        try {
            $path = $validated['manuscript']->store('research/manuscripts', $disk);
        } catch (FlysystemException|AwsException $e) {
            Log::error('R2 upload failed on store()', ['error' => $e->getMessage()]);
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }

        if (! $path) {
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }

        $paper = ResearchPaper::create([
            'title'             => $validated['title'],
            'abstract'          => $validated['abstract'] ?? null,
            'keywords'          => $validated['keywords'] ?? null,
            'file_path'         => $path,
            'original_filename' => $originalFilename,
            'course_id'         => $submitter->course_id,
            'submitted_by'      => $submitter->id,
            'adviser_id'        => $validated['adviser_id'] ?? null,
            'status'            => 'pending',
        ]);

        // Save authors from form input.
        $this->syncAuthors($paper, $validated['authors'] ?? [], $submitter);

        // Notify the assigned adviser.
        $this->notifyAdviser($paper, isResubmission: false);

        return $paper;
    }

    /**
     * Update an existing paper (resubmission after revision).
     * Replaces the file on disk if a new manuscript is provided.
     */
    public function update(array $validated, ResearchPaper $paper): ResearchPaper
    {
        $disk = config('filesystems.default');

        $originalFilename = null;

        if (isset($validated['manuscript'])) {
            /** @var FilesystemAdapter $storage */
            $storage = Storage::disk($disk);

            // Delete the old files — log failures but don't block the resubmission.
            try {
                $storage->delete($paper->file_path);
            } catch (FlysystemException|AwsException $e) {
                Log::warning('R2 delete failed for old manuscript on update()', [
                    'path'  => $paper->file_path,
                    'error' => $e->getMessage(),
                ]);
            }

            $originalFilename = $validated['manuscript']->getClientOriginalName();

            try {
                $validated['file_path'] = $validated['manuscript']->store('research/manuscripts', $disk);
            } catch (FlysystemException|AwsException $e) {
                Log::error('R2 upload failed on update()', ['error' => $e->getMessage()]);
                abort(503, 'Cloud storage is currently unavailable. Please try again later.');
            }

            if (! ($validated['file_path'] ?? false)) {
                abort(503, 'Cloud storage is currently unavailable. Please try again later.');
            }
        }

        $newFilePath = $validated['file_path'] ?? $paper->file_path;

        $updateData = [
            'title'      => $validated['title'],
            'abstract'   => $validated['abstract'] ?? null,
            'keywords'   => $validated['keywords'] ?? null,
            'file_path'  => $newFilePath,
            'adviser_id' => $validated['adviser_id'] ?? $paper->adviser_id,
            'status'     => 'pending',
        ];

        if ($originalFilename) {
            $updateData['original_filename'] = $originalFilename;
        }

        if (isset($validated['manuscript'])) {
            $paper->annotations()->delete();
        }

        $paper->update($updateData);

        // Re-sync authors from form input.
        $this->syncAuthors($paper, $validated['authors'] ?? [], $paper->submitter);

        $paper = $paper->fresh();

        // Notify the assigned adviser of the resubmission.
        $this->notifyAdviser($paper, isResubmission: true);

        return $paper;
    }

    /**
     * Record an adviser's review and transition the paper's status.
     */
    public function submitReview(ResearchPaper $paper, array $validated, User $reviewer): Review
    {
        $review = Review::create([
            'research_paper_id' => $paper->id,
            'reviewer_id'       => $reviewer->id,
            'comments'          => $validated['comments'],
            'decision'          => $validated['decision'],
        ]);

        $isApproved = $validated['decision'] === 'approved';

        $paper->update([
            'status'         => $isApproved ? 'approved' : 'revision',
            'published_year' => $isApproved ? now()->year : $paper->published_year,
        ]);

        // Notify the student who submitted the paper
        $paper->load('submitter');
        $paper->submitter->notify(new ResearchReviewed($paper, $review));

        return $review;
    }

    /**
     * Serve a secure file download, respecting the active disk.
     *
     * R2 uses a 30-minute presigned temporary URL (S3-compatible GET).
     * Local disk streams the file directly through the Laravel response.
     */
    public function download(ResearchPaper $paper): StreamedResponse
    {
        $diskName = config('filesystems.default');

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        try {
            abort_unless(
                $disk->exists($paper->file_path),
                404,
                'The requested file could not be found in storage.'
            );
        } catch (FlysystemException|AwsException $e) {
            Log::error('R2 exists() check failed on download()', [
                'paper_id' => $paper->id,
                'path'     => $paper->file_path,
                'error'    => $e->getMessage(),
            ]);
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }

        try {
            // Stream the file directly through the app — no time-limited presigned URLs.
            // Access is controlled by middleware on the route.
            return $disk->download(
                $paper->file_path,
                $paper->original_filename ?? $paper->title . '.pdf'
            );
        } catch (FlysystemException|AwsException $e) {
            Log::error('R2 download() failed', [
                'paper_id' => $paper->id,
                'path'     => $paper->file_path,
                'error'    => $e->getMessage(),
            ]);
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }
    }

    /**
     * Serve a file inline in the browser (for preview).
     * Sets Content-Disposition: inline so the browser renders PDF files without prompting a download.
     */
    public function serveInline(ResearchPaper $paper): StreamedResponse
    {
        $diskName = config('filesystems.default');

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($diskName);

        try {
            abort_unless(
                $disk->exists($paper->file_path),
                404,
                'The requested file could not be found in storage.'
            );
        } catch (FlysystemException|AwsException $e) {
            Log::error('R2 exists() check failed on serveInline()', [
                'paper_id' => $paper->id,
                'path'     => $paper->file_path,
                'error'    => $e->getMessage(),
            ]);
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }

        try {
            $extension = strtolower(pathinfo($paper->original_filename ?? $paper->file_path, PATHINFO_EXTENSION));
            $mime = $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                : 'application/pdf';

            return $disk->response(
                $paper->file_path,
                $paper->original_filename ?? $paper->title,
                ['Content-Type' => $mime]
            );
        } catch (FlysystemException|AwsException $e) {
            Log::error('R2 serveInline() failed', [
                'paper_id' => $paper->id,
                'path'     => $paper->file_path,
                'error'    => $e->getMessage(),
            ]);
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }
    }

    /**
     * Notify the assigned adviser (if any) that a paper was submitted or resubmitted.
     */
    private function notifyAdviser(ResearchPaper $paper, bool $isResubmission): void
    {
        if (! $paper->adviser_id) {
            return;
        }

        $paper->loadMissing(['submitter', 'adviser']);
        $paper->adviser->notify(new ResearchSubmitted($paper, $isResubmission));
    }

    /**
     * Admin direct upload — store an old/existing paper as approved.
     * No notifications sent, no student submitter required.
     */
    public function storeDirectUpload(array $validated, User $admin): ResearchPaper
    {
        $disk = config('filesystems.default');
        $originalFilename = $validated['manuscript']->getClientOriginalName();

        try {
            $path = $validated['manuscript']->store('research/manuscripts', $disk);
        } catch (FlysystemException|AwsException $e) {
            Log::error('R2 upload failed on storeDirectUpload()', ['error' => $e->getMessage()]);
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }

        if (! $path) {
            abort(503, 'Cloud storage is currently unavailable. Please try again later.');
        }

        $paper = ResearchPaper::create([
            'title'             => $validated['title'],
            'abstract'          => $validated['abstract'] ?? null,
            'keywords'          => $validated['keywords'] ?? null,
            'file_path'         => $path,
            'original_filename' => $originalFilename,
            'course_id'         => $validated['course_id'],
            'submitted_by'      => $admin->id,
            'adviser_id'        => $validated['adviser_id'] ?? null,
            'status'            => 'approved',
            'published_year'    => $validated['published_year'],
        ]);

        // Save authors from form input — no submitter guarantee needed.
        foreach ($validated['authors'] ?? [] as $index => $authorData) {
            ResearchAuthor::create([
                'research_paper_id' => $paper->id,
                'first_name'        => $authorData['first_name'],
                'last_name'         => $authorData['last_name'],
                'is_submitter'      => false,
                'sort_order'        => $index,
            ]);
        }

        return $paper;
    }

    /**
     * Replace all authors for a paper with the provided array.
     * Ensures the submitter is always present as the first author.
     */
    private function syncAuthors(ResearchPaper $paper, array $authorsInput, User $submitter): void
    {
        $paper->authors()->delete();

        $hasSubmitter = false;

        foreach ($authorsInput as $index => $authorData) {
            $isSubmitter = (bool) ($authorData['is_submitter'] ?? false);
            if ($isSubmitter) {
                $hasSubmitter = true;
            }

            ResearchAuthor::create([
                'research_paper_id' => $paper->id,
                'first_name'        => $authorData['first_name'],
                'last_name'         => $authorData['last_name'],
                'is_submitter'      => $isSubmitter,
                'sort_order'        => $index,
            ]);
        }

        // Guarantee submitter is present even if form somehow omitted them.
        if (! $hasSubmitter) {
            ResearchAuthor::create([
                'research_paper_id' => $paper->id,
                'first_name'        => $submitter->first_name,
                'last_name'         => $submitter->last_name,
                'is_submitter'      => true,
                'sort_order'        => 0,
            ]);
        }
    }
}
