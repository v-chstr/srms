<?php

namespace App\Notifications;

use App\Models\Queue;
use App\Models\QueueGroup;
use Illuminate\Notifications\Notification;

class QueueTurnNotification extends Notification
{
    public function __construct(
        private readonly Queue $queue,
        private readonly QueueGroup $group,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $total      = $this->queue->groups()->count();
        $position   = $this->group->position;
        $courseName = $this->queue->course->name ?? 'N/A';

        return [
            'type'       => 'queue_turn',
            'queue_id'   => $this->queue->id,
            'queue_title'=> $this->queue->title,
            'group_no'   => $position,
            'total'      => $total,
            'course'     => $courseName,
            'message'    => "It's your group's turn! You are group {$position} of {$total} in the {$this->queue->title} queue ({$courseName}).",
            'url'        => route('dashboard'),
        ];
    }
}
