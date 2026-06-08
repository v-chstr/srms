<?php

namespace Database\Seeders;

use App\Models\ResearchAuthor;
use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ResearchPaperSeeder extends Seeder
{
    public function run(): void
    {
        $sourcePdfPath = $this->resolveSourcePdfPath();
        $disk = config('filesystems.default', 'local');

        $papers = [
            [
                'title' => 'Web Development Workflow for Student Research Monitoring',
                'abstract' => 'This study explores a web-based workflow for managing research submissions, adviser feedback, and revision cycles inside a student research management portal. It focuses on reducing turnaround time, improving visibility of paper status, and organizing document handoff between students and advisers.',
                'keywords' => ['web development', 'research workflow', 'student portal'],
                'submitter_email' => 'fortes.jherilyn@gmail.com',
                'coauthor_emails' => ['lopez.cyrenejoy@gmail.com', 'santos.markanthony@gmail.com'],
                'adviser_email' => 'pugeda.rucelj@gmail.com',
                'status' => 'approved',
            ],
            [
                'title' => 'Digital Clearance Tracking for Information Technology Students',
                'abstract' => 'This paper proposes a digital clearance tracking process that centralizes submission checkpoints for graduating students. The system records task completion, highlights pending requirements, and gives advisers a clearer view of student progress across the semester.',
                'keywords' => ['digital clearance', 'student records', 'process automation'],
                'submitter_email' => 'reyes.angelica@gmail.com',
                'coauthor_emails' => ['garcia.mikaela@gmail.com', 'ramos.christian@gmail.com'],
                'adviser_email' => 'pugeda.rucelj@gmail.com',
                'status' => 'pending',
            ],
            [
                'title' => 'Embedded Classroom Monitoring for Computer Engineering Laboratories',
                'abstract' => 'The study evaluates an embedded monitoring setup for laboratory rooms that captures environmental data and equipment readiness before class sessions begin. The project emphasizes low-cost sensing, reliable alerting, and a usable reporting interface for instructors.',
                'keywords' => ['embedded systems', 'classroom monitoring', 'computer engineering'],
                'submitter_email' => 'bautista.jose@gmail.com',
                'coauthor_emails' => ['castillo.patricia@gmail.com', 'torres.ryan@gmail.com'],
                'adviser_email' => 'babaran.carlosjr@gmail.com',
                'status' => 'approved',
            ],
            [
                'title' => 'Metadata Classification Support for Campus Library Repositories',
                'abstract' => 'This research examines how structured metadata entry and controlled keyword usage can improve retrieval quality in a campus repository. It looks at consistency in tagging, discoverability of stored documents, and the effect of well-formed descriptors on archive browsing.',
                'keywords' => ['metadata classification', 'library repository', 'information retrieval'],
                'submitter_email' => 'buenaventura.rex@gmail.com',
                'coauthor_emails' => ['ocampo.mary.joy@gmail.com', 'austria.joel@gmail.com'],
                'adviser_email' => 'kummer.marifelgrace@gmail.com',
                'status' => 'revision',
            ],
        ];

        $emails = collect($papers)
            ->flatMap(fn (array $paper) => array_filter([
                $paper['submitter_email'],
                $paper['adviser_email'],
                ...$paper['coauthor_emails'],
            ]))
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        foreach ($papers as $paperData) {
            $submitter = $users->get($paperData['submitter_email']);

            if (! $submitter || ! $submitter->course_id) {
                throw new RuntimeException("Seed submitter [{$paperData['submitter_email']}] is missing or has no course.");
            }

            $adviser = $users->get($paperData['adviser_email']);
            $storagePath = 'research/manuscripts/seeded/' . Str::slug($paperData['title']) . '.pdf';

            Storage::disk($disk)->put($storagePath, File::get($sourcePdfPath));

            $paper = ResearchPaper::updateOrCreate(
                [
                    'title' => $paperData['title'],
                    'submitted_by' => $submitter->id,
                ],
                [
                    'abstract' => $paperData['abstract'],
                    'keywords' => $paperData['keywords'],
                    'file_path' => $storagePath,
                    'original_filename' => basename($sourcePdfPath),
                    'status' => $paperData['status'],
                    'course_id' => $submitter->course_id,
                    'adviser_id' => $adviser?->id,
                    'published_year' => $paperData['status'] === 'approved' ? (int) now()->format('Y') : null,
                ]
            );

            $authorEmails = collect([$paperData['submitter_email'], ...$paperData['coauthor_emails']])
                ->unique()
                ->values();

            $paper->authors()->delete();

            foreach ($authorEmails as $index => $email) {
                $author = $users->get($email);

                if (! $author) {
                    throw new RuntimeException("Seed author [{$email}] was not found.");
                }

                ResearchAuthor::create([
                    'research_paper_id' => $paper->id,
                    'first_name' => $author->first_name,
                    'last_name' => $author->last_name,
                    'is_submitter' => $email === $paperData['submitter_email'],
                    'sort_order' => $index,
                ]);
            }
        }
    }

    private function resolveSourcePdfPath(): string
    {
        $userProfile = getenv('USERPROFILE') ?: '';

        $candidates = array_filter([
            // Project root — drop the PDF here for seeding
            base_path('2020-Scrum-Guide-US.pdf'),
            base_path('scrumguide.pdf'),
            base_path('seed.pdf'),
            // database/seeders/files/
            database_path('seeders/files/seed.pdf'),
            database_path('seeders/files/2020-Scrum-Guide-US.pdf'),
            // User Downloads / Documents fallback
            $userProfile ? $userProfile . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . 'scrumguide.pdf' : null,
            $userProfile ? $userProfile . DIRECTORY_SEPARATOR . 'Downloads' . DIRECTORY_SEPARATOR . '2020-Scrum-Guide-US.pdf' : null,
            $userProfile ? $userProfile . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'scrumguide.pdf' : null,
        ]);

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Seed source PDF not found. Place a PDF at: ' . base_path('2020-Scrum-Guide-US.pdf')
        );
    }
}