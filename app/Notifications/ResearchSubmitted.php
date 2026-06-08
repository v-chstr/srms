<?php

namespace App\Notifications;

use App\Models\ResearchPaper;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResearchSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        private ResearchPaper $paper,
        private bool $isResubmission = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $action = $this->isResubmission ? 'resubmitted' : 'submitted';
        $submitter = $this->paper->submitter;
        $name = $submitter
            ? trim($submitter->first_name . ' ' . $submitter->last_name)
            : 'A student';

        return [
            'type'     => 'research_submitted',
            'paper_id' => $this->paper->id,
            'title'    => $this->paper->title,
            'message'  => "{$name} has {$action} a research paper: \"{$this->paper->title}\".",
            'url'      => route('adviser.reviews.index'),
        ];
    }
}
