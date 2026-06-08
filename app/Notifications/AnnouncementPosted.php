<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementPosted extends Notification
{
    use Queueable;

    public function __construct(
        private Announcement $announcement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'announcement',
            'title'   => $this->announcement->title,
            'message' => $this->announcement->message,
        ];
    }
}
