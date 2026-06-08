<?php

namespace App\Services;

use App\Models\ResearchPaper;

class CitationService
{
    /**
     * Generate an APA 7th edition citation.
     *
     * Format: Author1 Last, F. M., & Author2 Last, F. M. (Year). Title of paper. Department, Institution.
     */
    public function apa(ResearchPaper $paper): string
    {
        $authors = $this->formatAuthorsApa($paper);
        $year = $paper->published_year ?? $paper->created_at->year;
        $title = $paper->title;
        $course = $paper->course->name ?? '';

        return "{$authors} ({$year}). {$title}. {$course}, St. Paul University Philippines.";
    }

    /**
     * Generate an MLA 9th edition citation.
     *
     * Format: Last, First, and First Last. "Title." Department, St. Paul University Philippines, Year.
     */
    public function mla(ResearchPaper $paper): string
    {
        $authors = $this->formatAuthorsMla($paper);
        $year = $paper->published_year ?? $paper->created_at->year;
        $title = $paper->title;
        $course = $paper->course->name ?? '';

        return "{$authors} \"{$title}.\" {$course}, St. Paul University Philippines, {$year}.";
    }

    /**
     * Generate a Chicago/Turabian citation (notes-bibliography style).
     *
     * Format: Author1 Last, First, and First Last. "Title." Undergraduate thesis, St. Paul University Philippines, Year.
     */
    public function chicago(ResearchPaper $paper): string
    {
        $authors = $this->formatAuthorsChicago($paper);
        $year = $paper->published_year ?? $paper->created_at->year;
        $title = $paper->title;

        return "{$authors} \"{$title}.\" Undergraduate thesis, St. Paul University Philippines, {$year}.";
    }

    /**
     * Return all three citation formats at once.
     */
    public function all(ResearchPaper $paper): array
    {
        return [
            'apa'     => $this->apa($paper),
            'mla'     => $this->mla($paper),
            'chicago' => $this->chicago($paper),
        ];
    }

    // ─── Author Formatters ────────────────────────────────────────────

    /**
     * APA: "Last, F. M., & Last, F. M."
     */
    private function formatAuthorsApa(ResearchPaper $paper): string
    {
        $authors = $paper->authors;

        if ($authors->isEmpty()) {
            return $this->singleAuthorApa($paper->submitter);
        }

        $formatted = $authors->map(fn ($a) => $this->singleAuthorApa($a));

        if ($formatted->count() === 1) {
            return $formatted->first();
        }

        $last = $formatted->pop();
        return $formatted->implode(', ') . ', & ' . $last;
    }

    private function singleAuthorApa($user): string
    {
        $last = $user->last_name;
        $initials = collect(explode(' ', $user->first_name))
            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)) . '.')
            ->implode(' ');

        return "{$last}, {$initials}";
    }

    /**
     * MLA: "Last, First, and First Last."
     */
    private function formatAuthorsMla(ResearchPaper $paper): string
    {
        $authors = $paper->authors;

        if ($authors->isEmpty()) {
            $s = $paper->submitter;
            return "{$s->last_name}, {$s->first_name}.";
        }

        if ($authors->count() === 1) {
            $a = $authors->first();
            return "{$a->last_name}, {$a->first_name}.";
        }

        $first = $authors->first();
        $rest = $authors->slice(1);

        $parts = "{$first->last_name}, {$first->first_name}";

        if ($rest->count() === 1) {
            $second = $rest->first();
            $parts .= ", and {$second->first_name} {$second->last_name}.";
        } else {
            $last = $rest->pop();
            foreach ($rest as $a) {
                $parts .= ", {$a->first_name} {$a->last_name}";
            }
            $parts .= ", and {$last->first_name} {$last->last_name}.";
        }

        return $parts;
    }

    /**
     * Chicago: "Last, First, and First Last."
     */
    private function formatAuthorsChicago(ResearchPaper $paper): string
    {
        // Chicago author format is identical to MLA for this context.
        return $this->formatAuthorsMla($paper);
    }
}
