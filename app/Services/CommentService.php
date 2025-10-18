<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\Idea;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Get top-level comments for an idea with optimized eager loading.
     */
    public function getTopLevelCommentsForIdea(int $ideaId): \Illuminate\Database\Eloquent\Collection
    {
        return Comment::getTopLevelCommentsForIdea($ideaId);
    }

    /**
     * Get cached comment count for an idea.
     */
    public function getCommentCount(int $ideaId): int
    {
        return Comment::getCachedCommentCount($ideaId);
    }

    /**
     * Clear comment count cache when comments are added/deleted.
     */
    public function clearCommentCountCache(int $ideaId): void
    {
        Comment::clearCommentCountCache($ideaId);
    }

    /**
     * Create a new comment or reply with notifications.
     */
    public function createComment(array $data): Comment
    {
        $comment = Comment::create($data);

        // Clear cache for the idea
        $this->clearCommentCountCache($comment->idea_id);

        // Send notifications
        $this->sendCommentNotifications($comment);

        return $comment;
    }

    /**
     * Send notifications for new comments based on comment type.
     */
    private function sendCommentNotifications(Comment $comment): void
    {
        // Get the idea and its author
        $idea = Idea::with('user')->find($comment->idea_id);
        $ideaAuthor = $idea->user;

        // Don't notify if the commenter is the same as the idea author
        if ($comment->user_id === $ideaAuthor->id) {
            return;
        }

        if ($comment->parent_id === null) {
            // Top-level comment: notify idea author
            $this->notificationService->info(
                $ideaAuthor,
                'New Comment on Your Idea',
                "{$comment->user->first_name} {$comment->user->other_names} commented on your idea '{$idea->idea_title}'.",
                route('ideas.comments', ['idea' => $idea->slug])
            );
        } else {
            // Reply to a comment: notify both idea author and parent comment author
            $parentComment = Comment::with('user')->find($comment->parent_id);
            $parentCommentAuthor = $parentComment->user;

            // Notify idea author (if different from parent comment author)
            if ($ideaAuthor->id !== $parentCommentAuthor->id) {
                $this->notificationService->info(
                    $ideaAuthor,
                    'New Reply on Your Idea',
                    "{$comment->user->first_name} {$comment->user->other_names} replied to a comment on your idea '{$idea->idea_title}'.",
                    route('ideas.comments', ['idea' => $idea->slug])
                );
            }

            // Notify parent comment author (if different from current commenter)
            if ($parentCommentAuthor->id !== $comment->user_id) {
                $this->notificationService->info(
                    $parentCommentAuthor,
                    'New Reply to Your Comment',
                    "{$comment->user->first_name} {$comment->user->other_names} replied to your comment on '{$idea->idea_title}'.",
                    route('ideas.comments', ['idea' => $idea->slug])
                );
            }
        }
    }

    /**
     * Delete a comment and update cache.
     */
    public function deleteComment(Comment $comment): bool
    {
        $ideaId = $comment->idea_id;
        $result = $comment->delete();

        if ($result) {
            $this->clearCommentCountCache($ideaId);
        }

        return $result;
    }

    /**
     * Mark comment as read by current user.
     */
    public function markAsRead(Comment $comment, int $userId): void
    {
        // This would typically involve a pivot table for read status
        // For now, we'll implement a simple cache-based approach
        $cacheKey = "comment.{$comment->id}.read_by.{$userId}";
        Cache::put($cacheKey, true, now()->addDays(30));
    }

    /**
     * Check if comment is read by user.
     */
    public function isReadByUser(Comment $comment, int $userId): bool
    {
        $cacheKey = "comment.{$comment->id}.read_by.{$userId}";
        return Cache::has($cacheKey);
    }
}