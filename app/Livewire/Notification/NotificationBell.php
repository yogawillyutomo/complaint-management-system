<?php

namespace App\Livewire\Notification;

use Livewire\Component;

class NotificationBell extends Component
{
    public function markRead(string $notificationId): void
    {
        $notification = auth()->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();

        if (! $notification) {
            return;
        }

        $notification->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()
            ->unreadNotifications
            ->each
            ->markAsRead();
    }

    public function render()
    {
        return view('livewire.notification.notification-bell', [
            'notifications' => auth()->user()
                ->notifications()
                ->latest()
                ->take(8)
                ->get(),
            'unreadCount' => auth()->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }
}
