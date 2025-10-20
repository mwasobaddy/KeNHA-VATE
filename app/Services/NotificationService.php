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
     * Notify about collaboration invitation sent.
     */
    public function notifyCollaborationInvitationSent(User $invitee, User $inviter, string $ideaTitle, ?string $message = null): void
    {
        $this->info(
            $invitee,
            'Collaboration Invitation',
            "You've been invited to collaborate on '{$ideaTitle}' by {$inviter->name}." .
            ($message ? " Message: {$message}" : ''),
            route('ideas.collaboration.requests')
        );
    }

    /**
     * Notify about collaboration invitation accepted.
     */
    public function notifyCollaborationInvitationAccepted(User $inviter, User $invitee, string $ideaTitle): void
    {
        $this->success(
            $inviter,
            'Collaboration Invitation Accepted',
            "{$invitee->name} has accepted your invitation to collaborate on '{$ideaTitle}'!",
            route('ideas.show', \Str::slug($ideaTitle))
        );
    }

    /**
     * Notify about collaboration invitation declined.
     */
    public function notifyCollaborationInvitationDeclined(User $inviter, User $invitee, string $ideaTitle, ?string $reason = null): void
    {
        $this->warning(
            $inviter,
            'Collaboration Invitation Declined',
            "{$invitee->name} has declined your invitation to collaborate on '{$ideaTitle}'." .
            ($reason ? " Reason: {$reason}" : ''),
            route('ideas.show', \Str::slug($ideaTitle))
        );
    }

    /**
     * Notify about new revision suggestion.
     */
    public function notifyRevisionSuggestion(User $author, User $collaborator, string $ideaTitle, string $summary): void
    {
        $this->info(
            $author,
            'New Revision Suggestion',
            "{$collaborator->name} has suggested changes to your idea '{$ideaTitle}': {$summary}",
            route('ideas.show', \Str::slug($ideaTitle)) . '#revisions'
        );
    }

    /**
     * Notify about revision accepted.
     */
    public function notifyRevisionAccepted(User $collaborator, string $ideaTitle): void
    {
        $this->success(
            $collaborator,
            'Revision Accepted',
            "Your suggested changes to '{$ideaTitle}' have been accepted!",
            route('ideas.show', \Str::slug($ideaTitle))
        );
    }

    /**
     * Notify about revision rejected.
     */
    public function notifyRevisionRejected(User $collaborator, string $ideaTitle, ?string $reason = null): void
    {
        $this->warning(
            $collaborator,
            'Revision Rejected',
            "Your suggested changes to '{$ideaTitle}' were not accepted." .
            ($reason ? " Reason: {$reason}" : ''),
            route('ideas.show', \Str::slug($ideaTitle))
        );
    }

    /**
     * Notify about collaborator permissions updated.
     */
    public function notifyCollaboratorPermissionsUpdated(User $collaborator, string $ideaTitle, string $permissions): void
    {
        $this->info(
            $collaborator,
            'Collaboration Permissions Updated',
            "Your permissions for '{$ideaTitle}' have been updated to: {$permissions}",
            route('ideas.show', \Str::slug($ideaTitle))
        );
    }

    /**
     * Notify about collaborator removed.
     */
    public function notifyCollaboratorRemoved(User $collaborator, string $ideaTitle, ?string $reason = null): void
    {
        $this->warning(
            $collaborator,
            'Removed from Collaboration',
            "You have been removed from collaborating on '{$ideaTitle}'." .
            ($reason ? " Reason: {$reason}" : ''),
            route('ideas.show', \Str::slug($ideaTitle))
        );
    }

    /**
     * Notify about idea rollback.
     */
    public function notifyIdeaRollback(Collection $collaborators, string $ideaTitle): void
    {
        foreach ($collaborators as $collaborator) {
            $this->info(
                $collaborator->user,
                'Idea Revision Rollback',
                "The author of '{$ideaTitle}' has rolled back to an earlier version.",
                route('ideas.show', \Str::slug($ideaTitle))
            );
        }
    }

    /**
     * Notify about new comment on collaborative idea.
     */
    public function notifyNewComment(User $recipient, User $commenter, string $ideaTitle, string $commentPreview): void
    {
        $this->info(
            $recipient,
            'New Comment on Idea',
            "{$commenter->name} commented on '{$ideaTitle}': " . \Str::limit($commentPreview, 100),
            route('ideas.show', \Str::slug($ideaTitle)) . '#comments'
        );
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