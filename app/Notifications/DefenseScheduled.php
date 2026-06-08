<?php

namespace App\Notifications;

use App\Models\DefenseSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DefenseScheduled extends Notification {
    use Queueable;

    public function __construct(
        private DefenseSchedule $schedule,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'defense_scheduled',
            'schedule_id' => $this->schedule->id,
            'title'       => $this->schedule->title,
            'date'        => $this->schedule->scheduled_date->format('M d, Y'),
            'time'        => $this->schedule->start_time->format('g:i A'),
            'room'        => $this->schedule->room,
            'course'      => $this->schedule->course?->displayCode(),
            'message'     => "Defense scheduled: \"{$this->schedule->title}\" on {$this->schedule->scheduled_date->format('M d, Y')} at {$this->schedule->start_time->format('g:i A')}.",
            'url'         => route('dashboard'),
        ];
    }
}
