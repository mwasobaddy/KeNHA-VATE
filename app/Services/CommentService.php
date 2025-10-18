<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

class CommentService
{
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
     * Create a new comment or reply.
     */
    public function createComment(array $data): Comment
    {
        $comment = Comment::create($data);

        // Clear cache for the idea
        $this->clearCommentCountCache($comment->idea_id);

        return $comment;
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