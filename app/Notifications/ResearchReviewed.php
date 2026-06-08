<?php

namespace App\Notifications;

use App\Models\ResearchPaper;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResearchReviewed extends Notification
{
    use Queueable;

    public function __construct(
        private ResearchPaper $paper,
        private Review $review,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $decision = $this->review->decision === 'approved' ? 'approved' : 'needs revision';

        return [
            'type'     => 'research_reviewed',
            'paper_id' => $this->paper->id,
            'title'    => $this->paper->title,
            'decision' => $this->review->decision,
            'message'  => "Your paper \"{$this->paper->title}\" has been reviewed: {$decision}.",
            'url'      => route('student.research.index'),
        ];
    }
}

