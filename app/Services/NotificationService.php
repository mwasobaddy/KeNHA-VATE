<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send a notification to a user.
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): void {
        // Create in-app notification
        $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);

        // TODO: Send email notification if needed
        // This could be extended to send emails for certain types of notifications
    }

    /**
     * Send a success notification.
     */
    public function success(User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        $this->notify($user, 'success', $title, $message, $actionUrl);
    }

    /**
     * Send an error notification.
     */
    public function error(User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        $this->notify($user, 'error', $title, $message, $actionUrl);
    }

    /**
     * Send an info notification.
     */
    public function info(User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        $this->notify($user, 'info', $title, $message, $actionUrl);
    }

    /**
     * Send a warning notification.
     */
    public function warning(User $user, string $title, string $message, ?string $actionUrl = null): void
    {
        $this->notify($user, 'warning', $title, $message, $actionUrl);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId): bool
    {
        $notification = Notification::find($notificationId);

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Get unread notifications count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }
}