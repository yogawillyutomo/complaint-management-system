<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Complaint $complaint,
        public string $eventType,
        public string $title,
        public string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'ticket_number' => $this->complaint->ticket_number,
            'route' => 'complaints.show',
        ];
    }
}
