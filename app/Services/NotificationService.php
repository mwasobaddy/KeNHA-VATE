<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}
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
        $notification = $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
        ]);

        // Audit critical notifications
        if ($this->isCriticalNotification($type, $title)) {
            $this->auditService->logUserActivity($user, 'notification_sent', [
                'notification_id' => $notification->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
            ]);
        }

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

    /**
     * Determine if a notification should be audited as critical.
     */
    private function isCriticalNotification(string $type, string $title): bool
    {
        // Critical notification types that should always be audited
        $criticalTypes = ['error', 'warning'];

        // Critical notification titles that should be audited
        $criticalTitles = [
            'Staff Approval Request',
            'Staff Approval Granted',
            'Staff Approval Rejected',
            'Points Awarded',
            'Account Status Changed',
            'Profile Update',
            'Security Alert',
            'Password Changed',
            'Email Verification',
        ];

        return in_array($type, $criticalTypes) ||
               in_array($title, $criticalTitles) ||
               str_contains(strtolower($title), 'approval') ||
               str_contains(strtolower($title), 'security') ||
               str_contains(strtolower($title), 'account');
    }
}